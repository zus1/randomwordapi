const editPage = document.getElementById("edit-page");
const editLocal = document.getElementById("edit-local");
const editHolder = document.getElementById("edit-holder");
const editContent = document.getElementById("edit-content");
const editButton = document.getElementById("edit-button");

editPage.addEventListener("change", () => loadPlaceholders(event));
editPage.addEventListener("change", () => resetContent());
editHolder.addEventListener("change", () => loadContent(event));
editButton.addEventListener("click", () => contentEdit());

function contentEdit() {
    if(editPage.value === "") {
        addNotification("error", "Please select page to edit", "edit");
    } else if(editLocal.value === "") {
        addNotification("error", "Please select local", "edit");
    } else if(editHolder.value === "") {
        addNotification("error", "Please select placeholder", "edit");
    } else {
        const formData = new FormData();
        formData.append("page-name", editPage.value);
        formData.append("local", editLocal.value);
        formData.append("placeholder", editHolder.value);
        formData.append("content", editContent.value);

        postAjax("/views/cms/editpagecontent.php", formData, postContentEdit)
    }
}

function postContentEdit(data) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, "edit");
}

$(document).ready(function () {
    $("#edit-content").cleditor({
        controls: // controls to add to the toolbar
            "bold italic underline strikethrough subscript superscript | font size " +
            "style | color highlight removeformat | bullets numbering | outdent " +
            "indent | alignleft center alignright justify | undo redo | " +
            " cut copy paste pastetext",
        bodyStyle: // style to assign to document body contained within the editor
            "margin:4px; font:10pt Arial,Verdana; cursor:text; background-color: #545b62; color: white"
    });
});

function loadContent(event) {
    if(event.target.value === "") {
        addNotification("error", "Please select placeholder for content to load", "edit");
    } else if(editLocal.value === "") {
        addNotification("error", "Please select local for content to load", "edit");
    } else if(editPage.value === "") {
        addNotification("error", "Please select page for content to load", "edit");
    } else {
        getAjax("/views/cms/getplaceholdercontent.php?page-name=" + editPage.value + "&local=" + editLocal.value + "&placeholder=" + editHolder.value, postLoadContent);
    }
}

function postLoadContent(data) {
    if(parseInt(data.error) === 1) {
        console.error(data.message);
    } else {
        editContent.value = data.content;
        $("#edit-content").cleditor()[0].updateFrame();
    }
}

function resetContent() {
    $("#edit-content").cleditor()[0].clear();
}

function loadPlaceholders(event) {
    if(event.target.value === "") {
        addNotification("error", "Please select page", "edit");
    } else {
        getAjax("/views/cms/getcontentplaceholders.php?page-name=" + event.target.value, postLoadPlaceholders);
    }
}

function postLoadPlaceholders(data) {
    if(data.error === 1) {
        console.error(data.message);
    } else {
        resetOptionsAndPreserveFirstChild(editHolder, true)
        data.placeholders.forEach((element) => {
            createOptionElement(element, element, editHolder);
        });
    }
}