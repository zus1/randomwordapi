const addTag = document.getElementById("add-tag");
const addButton = document.getElementById("add-button");

const removeTag = document.getElementById("remove-tag");
const removeButton = document.getElementById("remove-button");

const changeTag = document.getElementById("change-tag");
const changeActive = document.getElementById("change-active");
const changeButton = document.getElementById("change-button");

addButton.addEventListener("click", () => addLocal());
removeButton.addEventListener("click", () => removeLocal());
changeTag.addEventListener("change", () => loadActive());
changeButton.addEventListener("click", () => changeLocal());

function loadActive() {
    getAjax("/views/adm/changegetlocalactive.php?tag=" + changeTag.value, postLoadActive)
}

function postLoadActive(data) {
    if(data.error === 0) {
        resetOptionsAndPreserveFirstChild(changeActive, false);
        if(parseInt(data.active) === 1) {
            createOptionElement(1, "true", changeActive, true);
            createOptionElement(0, "false", changeActive, false);
        } else {
            createOptionElement(1, "true", changeActive, false);
            createOptionElement(0, "false", changeActive, true);
        }
    }
}

function changeLocal() {
    if(changeTag.value === "") {
        addNotification("error", "Please select Local", "change");
    } else if(changeActive.value === "") {
        addNotification("error", "Please select active state", "change");
    } else {
        const formData = new FormData();
        formData.append("tag", changeTag.value);
        formData.append("active", changeActive.value);

        postAjax("/views/adm/changelocalactive.php", formData, postChangeLocal)
    }
}

function postChangeLocal(data) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, "change");
}

function removeLocal() {
    if(removeTag.value === "") {
        addNotification("error", "Please select tag", "remove");
    } else {
        const formData = new FormData();
        formData.append("tag", removeTag.value);

        postAjax("/views/adm/removelocal.php", formData, postRemoveLocal)
    }
}

function postRemoveLocal(data) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, "remove");
}

function addLocal() {
    if(addTag.value === "") {
        addNotification("error", "Tag can't be empty", "add");
    } else {
        const formData = new FormData();
        formData.append("tag", addTag.value);

        postAjax("/views/adm/addlocal.php", formData, postAddLocal)
    }
}

function postAddLocal(data) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, "add");
}