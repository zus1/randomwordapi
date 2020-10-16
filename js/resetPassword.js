const messageDiv = document.getElementById("message-div");
const formDiv = document.getElementById("form-div");
const status = document.getElementById("status");
const resetForm = document.getElementById("reset-form");

window.addEventListener("load", () => chooseDiv());
resetForm.addEventListener("submit", () => resetFormOnSubmit(event));

function chooseDiv() {
    if(parseInt(status.value) === 1) {
        messageDiv.style.display = "block";
        formDiv.style.display = "none";
    }
}

function resetFormOnSubmit(event) {
    event.preventDefault();
    event.target.token.value = location.search.replace("?", "").split("&")[0].split("=")[1];
    event.target.submit();
}