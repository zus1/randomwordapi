const statusIndicator = document.getElementById("status-indicator");
const resendEmailDiv = document.getElementById("resend-email-div");
window.addEventListener("load", () => {
    allowResend();
    redirect();
});

function redirect() {
    if(parseInt(statusIndicator.value) === 2) {
        const http = getHttpParams();
        setTimeout(() => {
            location.href = http.protocol + "//" + http.host + "/views/auth/login.php";
        }, 3000);
    }
}

function allowResend() {
    if(parseInt(statusIndicator.value) === 1) {
        resendEmailDiv.style.display = "block";
    }
}