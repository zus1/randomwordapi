const csvWordsButton = document.getElementById("csv-words-button");
const csvWords = document.getElementById("csv-words");
const csvWordFiles = document.getElementById("csv-word-files");

csvWordsButton.addEventListener("click", () => csvWords.click());
csvWords.addEventListener("change", () => displayFileName(event));

function displayFileName(evt) {
    const file = evt.target.files[0]
    csvWordFiles.innerHTML = "";
    csvWordFiles. innerHTML = file.name;
}