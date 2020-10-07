const languageSelect = document.getElementById("language");
const lengthSelect = document.getElementById("length");
const wordsSelect = document.getElementById("words");
const singleButton = document.getElementById("single-button");

const languageMultiSelect = document.getElementById("language-multi")
const wordsBulk = document.getElementById("words-bulk");
const wordsJson = document.getElementById("words-json");
const wordsCsv = document.getElementById("words-csv");
const csvWordsFiles = document.getElementById("csv-word-files");
const wordsCsvButton = document.getElementById("words-csv-button");
const clearCsvButton = document.getElementById("words-csv-clear-button");
const multipleButton = document.getElementById("multiple-button");

languageSelect.addEventListener("change", () => loadAvailableWordLengthsForLanguage(event));
lengthSelect.addEventListener("change", () => loadAvailableWordsForLength(event));
singleButton.addEventListener("click", () => sendSingleRemove());
wordsCsvButton.addEventListener("click", () => wordsCsv.click());
clearCsvButton.addEventListener("click", () => clearCsvFile());
multipleButton.addEventListener("click", () => sendMultiRemove());
wordsCsv.addEventListener("change", () =>displayFilename(event))

function displayFilename(event) {
    csvWordsFiles.innerHTML = event.target.files[0].name;
}

function clearCsvFile() {
    wordsCsv.value = "";
    csvWordsFiles.innerHTML = "";
}

function sendMultiRemove() {
    if(wordsBulk.value === "" && wordsJson.value === "" && wordsCsv.files.length === 0) {
        addNotification("error", "No words to remove", "multi")
    } else if(languageMultiSelect.value === "") {
        addNotification("error", "No language selected", "multi")
    } else {
        const formData = new FormData();
        formData.append("language", languageMultiSelect.value);
        formData.append("words-bulk", wordsBulk.value);
        formData.append("words-json", wordsJson.value);
        formData.append("words-csv", wordsCsv.files[0]);

        postAjax('/views/adm/removemulti.php', formData, multiRemoved)
    }
}

function multiRemoved(data) {
    postActionMessage(data, "multi");
}

function sendSingleRemove() {
    if(languageSelect.value === "") {
        addNotificationWithTranslation("error", "wappi_modify_remove-language-not-selected", "single");
    } else if(lengthSelect.value === "") {
        addNotification("error", "Length must be selected", "single")
    } else if(wordsSelect.value === "") {
        addNotification("error", "No words selected", "single")
    } else {
        const wordsToRemove = [];
        for(let i = 0; i < wordsSelect.options.length; i++) {
            if(wordsSelect.options[i].selected) {
                wordsToRemove.push(wordsSelect.options[i].value)
            }
        }

        const formData = new FormData();
        formData.append("language", languageSelect.value);
        formData.append("words", JSON.stringify(wordsToRemove));

        postAjax('/views/adm/removesingle.php', formData, singleRemoved)
    }

}

function singleRemoved(data) {
    postActionMessage(data, "single");
}

function loadAvailableWordLengthsForLanguage(event) {
    const element = event.target;
    if(element.value === "") {
        addNotification("error", "Language not selected", "single")
    } else {
        getAjax("/views/adm/ajaxlengths.php?language=" + element.value, addWordLengths)
    }
}

function loadAvailableWordsForLength(event) {
    const element = event.target;
    if(element.value === "") {
        addNotification("error", "Length not selected", "single")
    } else {
        getAjax("/views/adm/ajaxwords.php?length=" + element.value + "&language=" + languageSelect.value, addWords)
    }
}

function addWordLengths(data) {
    resetOptionsAndPreserveFirstChild(lengthSelect)
    for(let i = 0; i < data.lengths.length; i++) {
        createOptionElement(data.lengths[i].length, data.lengths[i].length, lengthSelect)
    }
}

function addWords(data) {
    resetOptionsAndPreserveFirstChild(wordsSelect)
    for(let i = 0; i < data.words.length; i++) {
        createOptionElement(data.words[i], data.words[i], wordsSelect)
    }
}