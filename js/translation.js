const addTag = document.getElementById("add-tag");
const addKey = document.getElementById("add-key");
const addTranslation = document.getElementById("add-translation");
const addButton = document.getElementById("add-button");

const editTag = document.getElementById("edit-tag");
const editKey = document.getElementById("edit-key");
const editTranslation = document.getElementById("edit-translation");
const editButton = document.getElementById("edit-button");

const removeTag = document.getElementById("remove-tag");
const removeKey = document.getElementById("remove-key");
const removeButton = document.getElementById("remove-button");

addButton.addEventListener("click", () => translationAdd())
editTag.addEventListener("change", () => {
    loadKeys(event, "edit");
    clearTranslation();
});
editKey.addEventListener("change", () => loadTranslation(event));
editButton.addEventListener("click", () => translationEdit());
removeTag.addEventListener("change", () => loadKeys(event, "remove"));
removeButton.addEventListener("click", () => translationRemove());

function translationRemove() {
    if(removeTag.value === "") {
        addNotification("error", "Please select local", "remove");
    } else if(removeKey.value === "") {
        addNotification("error", "Please select translation key to remove", "remove");
    } else {
        const formData = new FormData();
        formData.append("local", removeTag.value);
        formData.append("key", removeKey.value);

        postAjax("/views/adm/removetranslation.php", formData, postTranslationRemove);
    }
}

function postTranslationRemove(data) {
    postActionMessage(data, "remove");
}

function translationAdd() {
    if(addTag.value === "") {
        addNotification("error", "Please select local", "add");
    } else if(addKey.value === "") {
        addNotification("error", "Key can't be empty", "add");
    } else if(addTranslation.value === "") {
        addNotification("error", "Translation can't be empty", "add");
    } else {
        const formData = new FormData();
        formData.append("local", addTag.value);
        formData.append("key", addKey.value);
        formData.append("translation", addTranslation.value);

        postAjax("/views/adm/addtranslation.php", formData, postTranslationAdd);
    }
}

function postTranslationAdd(data) {
    postActionMessage(data, "add");
}

function translationEdit() {
    if(editTag.value === "") {
        addNotification("error", "Please select local", "edit");
    } else if(editKey.value === "") {
        addNotification("error", "Please select translation key", "edit");
    } else if(editTranslation.value === "") {
        addNotification("error", "Translation can't be empty", "edit");
    } else {
        const formData = new FormData();
        formData.append("local", editTag.value);
        formData.append("key", editKey.value);
        formData.append("translation", editTranslation.value);

        postAjax("/views/adm/edittranslation.php", formData, postTranslationEdit);
    }
}

function postTranslationEdit(data) {
    postActionMessage(data, "edit");
}

function loadTranslation(event) {
    if(editTag.value === "") {
        addNotification("error", "Please select local first to load translation", "edit");
    } else if(event.target.value === "") {
        addNotification("error", "Please select translation key first, to load translation", "edit");
    } else {
        getAjax("/views/adm/translationload?local=" + editTag.value + "&key=" + event.target.value, postLoadTranslation);
    }
}

function postLoadTranslation(data) {
    if(parseInt(data.error) === 1) {
        console.error(data.message);
    } else {
        editTranslation.value = data.translation;
    }
}

function clearTranslation() {
    editTranslation.value = "";
}

function loadKeys(event, source) {
    if(event.target.value === "") {
        addNotification("error", "Please select local", source);
    } else {
        getAjax("/views/adm/translationgetkeys?local=" + event.target.value + "&source=" + source, postLoadKeys);
    }
}

function postLoadKeys(data) {
    if(parseInt(data.error) === 1) {
        console.error(data.message);
    } else {
        let obj;
        if(data.source === "edit") {
            obj = editKey;
        } else {
            obj = removeKey;
        }
        resetOptionsAndPreserveFirstChild(obj, true);
        data.keys.forEach((key) => createOptionElement(key, key, obj));
    }
}