function generatePassword() {
    const password = document.getElementById("pswd");
    const passwordLength = 16;
    const chars = "123456789abcdefgahijklsABCDFLAQWERT_@?.!*-+<>";
    let passwordStr = "";
    for(let i = passwordLength; i > 0; i--) {
        const rand = Math.floor(Math.random() * chars.length);
        passwordStr = passwordStr + chars[rand];
    }
    password.value = passwordStr;
}

function passwordShow() {
    const elements = passwordShowHideElements();
    elements.pswd.type = "text";
    elements.confirm_pswd.type = "text";
    elements.show_div.style.display = "none";
    elements.hide_div.style.display = "block";
}

function passwordHide() {
    const elements = passwordShowHideElements();
    elements.pswd.type = "password";
    elements.confirm_pswd.type = "password";
    elements.show_div.style.display = "block";
    elements.hide_div.style.display = "none";
}

function passwordShowHideElements() {
    return {
        "pswd": document.getElementById("pswd"),
        "confirm_pswd": document.getElementById("pswd-confirm"),
        "show_div": document.getElementById("show"),
        "hide_div": document.getElementById("hide"),
    }
}