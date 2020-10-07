const csvWordsButton = document.getElementById("csv-words-button");
const csvWords = document.getElementById("csv-words");
const csvWordFiles = document.getElementById("csv-word-files");
const clearFilesButton = document.getElementById("csv-words-clear");

csvWordsButton.addEventListener("click", () => csvWords.click());
csvWords.addEventListener("change", () => displayFileName(event));
clearFilesButton.addEventListener("click", () => clearFiles())

function clearFiles() {
    csvWords.value = "";
    csvWordFiles.innerHTML = "";
}

function displayFileName(evt) {
    const file = evt.target.files[0]
    csvWordFiles.innerHTML = "";
    csvWordFiles. innerHTML = file.name;
}