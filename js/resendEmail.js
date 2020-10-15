function resendEmail() {
    const elements = getResendAndLoadingElements();
    console.log(window.location.search);
    const params = window.location.search.replace("?", "");
    if(params.length > 0) {
        elements.resend_element.style.display = "none";
        elements.loading_element.style.display = "block";
        const uuid = params.split("&")[0].split("=")[1];
        const formData = new FormData();
        formData.append("uuid", uuid);
        postAjax("/views/auth/resendemail.php", formData, emailResent);
    }
}

function emailResent(data) {
    const elements = getResendAndLoadingElements();
    elements.resend_element.style.display = "block";
    elements.loading_element.style.display = "none";
    if(parseInt(data.error) === 1) {
        addNotification("error", data.message, "verify");
    } else {
        addNotification("success", data.message, "verify");
    }
}

function getResendAndLoadingElements() {
    return {
        "resend_element": document.getElementById("resend-email-div"),
        "loading_element": document.getElementById("loading-div"),
    }
}