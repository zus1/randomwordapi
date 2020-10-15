function regenerateAccessToken(buttonId) {
    const idParts = buttonId.split("-");
    const id = idParts[idParts.length - 1];

    const currentTokenDiv = document.getElementById(id);
    const currentToken = currentTokenDiv.innerHTML.split(":")[1].trim();

    const formData = new FormData();
    formData.append("app_id", id);
    formData.append("current_token", currentToken);

    postAjax("/views/api/regeneratetoken.php", formData, tokenRegenerated);
}

function tokenRegenerated(data) {
    if(parseInt(data.error === 1)) {
        addNotification("error", data.message, "regenerate-" + data.id);
    } else {
        const tokenDiv = document.getElementById(data.id)
        tokenDiv.innerHTML = "Access token: " + data.new_token;
        addNotification("success", data.message, "regenerate-" + data.id);
    }
}

function deleteApp(buttonId) {
    const confirmation = confirm("You are about to delete app. This action is permanent and can't be reverted. Are you sure you want to proceed?");
    if(confirmation === true) {
        const idParts = buttonId.split("-");
        const id = idParts[idParts.length - 1];
        const formData = new FormData();
        formData.append("app_id", id);

        postAjax("/views/api/deleteapp.php", formData, afterAppDelete)
    }
}

function afterAppDelete(data) {
    if(parseInt(data.error) === 1) {
        addNotification("error", data.message, "regenerate-" + data.id);
    } else {
        location.reload();
    }
}