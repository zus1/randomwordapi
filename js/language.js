const tag = document.getElementById("tag");
const name = document.getElementById("name");
const filtersAdd = document.getElementById("filters-add")
const addButton = document.getElementById("add-button");

const languageRemove = document.getElementById("language-remove");
const removeButton = document.getElementById("remove-button");

const tagUpdate = document.getElementById("tag-update");
const nameUpdate = document.getElementById("name-update");
const filtersUpdate = document.getElementById("filters-update");
const updateButton = document.getElementById("update-button");

addButton.addEventListener("click", () => addLanguage());
removeButton.addEventListener("click", () => removeLanguage());
tagUpdate.addEventListener("change", () => updateGetNameAndFilters(event));
updateButton.addEventListener("click", () => updateLanguage());

function updateLanguage() {
    if(tagUpdate.value === "") {
        addNotification("error", "Please select language", "update");
    } else if(nameUpdate.value === "") {
        addNotification("error", "Name can't be empty", "update");
    }  else if(filtersUpdate.value === "") {
        addNotification("error", "Please select at least one filter", "update");
    } else {
        let filters = [];
        for(let i = 0; i < filtersUpdate.options.length; i++) {
            if(filtersUpdate.options[i].selected) {
                filters.push(filtersUpdate.options[i].value);
            }
        }
        const formData = new FormData();
        formData.append("tag", tagUpdate.value);
        formData.append("name", nameUpdate.value);
        formData.append("filters", JSON.stringify(filters));

        postAjax("/views/adm/doupdatelanguage.php", formData, languageUpdated);
    }
}

function languageUpdated(data) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, "update");
}

function updateGetNameAndFilters(event) {
    if(event.target.value === "") {
        addNotification("error", "Please select language", "update");
    } else {
        getAjax("/views/adm/updatelanguageresources.php?tag=" + event.target.value, updateFetchedNameAndFilters)
    }
}

function updateFetchedNameAndFilters(data) {
    if(data.error === 1) {
        addNotification("error", data.message, "update");
    } else {
        nameUpdate.value = data.name;
        resetOptionsAndPreserveFirstChild(filtersUpdate, false)

        for(let i = 0; i < data.filters.length; i++) {
            createOptionElement(data.filters[i].filter, data.filters[i].filter, filtersUpdate, data.filters[i].is_selected)
        }
    }
}

function removeLanguage() {
    if(languageRemove.value === "") {
        addNotification("error", "Please select language", "remove");
    } else {
        const formData = new FormData();
        formData.append("tag", languageRemove.value);

        postAjax("/views/adm/removelanguage.php", formData, languageRemoved);
    }
}

function languageRemoved(data) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, "remove");
}

function addLanguage() {
    if(tag.value === "") {
        addNotification("error", "Tag is missing", "add");
    } else if(name.value === "") {
        addNotification("error", "Name is missing", "add");
    } else if(filtersAdd.value === "") {
        addNotification("error", "Please choose filters", "add");
    } else {
        const filters = [];
        for(let i = 0; i < filtersAdd.options.length; i++) {
            if(filtersAdd.options[i].selected) {
                filters.push(filtersAdd.options[i].value);
            }
        }

        const formData = new FormData();
        formData.append("tag", tag.value);
        formData.append("name", name.value);
        formData.append("filters", JSON.stringify(filters));

        postAjax("/views/adm/addlanguage.php", formData, languageAdded);
    }
}

function languageAdded(data) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, "add");
}