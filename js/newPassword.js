const emailForm = document.getElementById("email-send-form");
const buttonDiv = document.getElementById("button-div");
const sendingDiv = document.getElementById("sending-div");
emailForm.addEventListener("submit", () => onEmailFormSubmit(event));

function onEmailFormSubmit(event) {
    event.preventDefault();
    buttonDiv.style.display = "none";
    sendingDiv.style.display = "block";
    event.target.submit();
}