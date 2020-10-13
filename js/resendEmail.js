function resendEmail() {
    console.log(window.location.search);
    const params = window.location.search.replace("?", "");
    if(params.length > 0) {
        const uuid = params.split("&")[0].split("=")[1];
        console.log(uuid);
        const formData = new FormData();
        formData.append("uuid", uuid);
        postAjax("/views/auth/resendemail.php", formData, emailResent);
    }
}

function emailResent(data) {
    if(parseInt(data.error) === 1) {
        addNotification("error", data.message, "verify");
    } else {
        addNotification("success", data.message, "verify");
    }
}