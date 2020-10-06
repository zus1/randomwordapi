const addName = document.getElementById("add-name");
const addHolders = document.getElementById("add-holders");
const addButton = document.getElementById("add-button");

const changePage = document.getElementById("change-page");
const changeName = document.getElementById("change-name");
const changeExistingHolders = document.getElementById("change-existing-holders");
const changeNewHolders = document.getElementById("change-new-holders");
const changeButton = document.getElementById("change-button");

const removePage = document.getElementById("remove-page");
const removeButton = document.getElementById("remove-button");

addButton.addEventListener("click", () => addPage());
changePage.addEventListener("change", () => loadNameAndPlaceholders(event));
changeButton.addEventListener("click", () => editPage());
removeButton.addEventListener("click", () => deletePage());

function deletePage() {
    if(removePage.value === "") {
        addNotification("error", "Please select page to be removed", "remove");
    } else {
        const formData = new FormData();
        formData.append("id", removePage.value);

        postAjax("/views/cms/removepages.php", formData, postDeletePage)
    }
}

function postDeletePage(data) {
    postActionMessage(data, "remove");
}

function editPage() {
    if(changePage.value === "") {
        addNotification("error", "Please select page", "change");
    } else if(changeName.value === "") {
        addNotification("error", "Name can't be empty", "change");
    } else {
        let changeHolders = [];
        for(let i = 0 ; i < changeExistingHolders.options.length; i++) {
            if(changeExistingHolders.options[i].selected) {
                changeHolders.push(changeExistingHolders.options[i].value);
            }
        }

        const formData = new FormData();
        formData.append("id", changePage.value);
        formData.append("name", changeName.value);
        formData.append("change-holders", JSON.stringify(changeHolders));
        formData.append("add-holders", changeNewHolders.value);

        postAjax("/views/cms/editpages.php", formData, postEditPage)
    }
}

function postEditPage(data) {
    postActionMessage(data, "change");
}

function loadNameAndPlaceholders(event) {
    getAjax("/views/cms/getnameandholders.php?id=" + event.target.value, postLoadNameAndPlaceholders)
}

function postLoadNameAndPlaceholders(data) {
    if(data.error === 1) {
        addNotification("error", data.message, "change");
    } else {
        if(data.name !== "") {
            changeName.value = data.name;
        }
        if(data.placeholders.length > 0) {
            resetOptionsAndPreserveFirstChild(changeExistingHolders, true);
            data.placeholders.forEach((element) => {
                createOptionElement(element, element, changeExistingHolders)
            });
        }
    }
}

function addPage() {
    if(addName.value === "") {
        addNotification("error", "Name can't be empty", "add");
    } else if(addHolders.value === "") {
        addNotification("error", "Please add at least one placeholder", "add");
    } else {
        const formData = new FormData();
        formData.append("name", addName.value);
        formData.append("placeholders", addHolders.value);

        postAjax("/views/cms/addpages.php", formData, postAddPage)
    }
}

function postAddPage(data) {
    postActionMessage(data, "add");
}