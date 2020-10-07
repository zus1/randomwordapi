const passwordGenerateButton = document.getElementById("generate-password");
const password = document.getElementById("pswd");

passwordGenerateButton.addEventListener("click", () => generatePassword());

function generatePassword() {
    const passwordLength = 16;
    const chars = "123456789abcdefgahijklsABCDFLAQWERT_@?.!*-+<>";
    let passwordStr = "";
    for(let i = passwordLength; i > 0; i--) {
        const rand = Math.floor(Math.random() * chars.length);
        passwordStr = passwordStr + chars[rand];
    }
    password.value = passwordStr;
}