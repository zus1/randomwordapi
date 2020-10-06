const localization = document.getElementsByName("localization");
if(localization.length > 0) {
    for(let i = 0; i < localization.length; i++) {
        localization[i].addEventListener("change", () => changeUserLocal(event));
    }
}

function changeUserLocal(event) {
    if(event.target.checked) {
        getAjax("/views/ajaxchangelocal?local=" + event.target.value, afterLocalChange)
    }
}

function afterLocalChange(data) {
    location.reload();
}

function createOptionElement(value, text, object, isSelected=false) {
    const element = document.createElement("option");
    element.setAttribute("value", value);
    if(isSelected === true) {
        element.selected = true;
    }
    const textNode = document.createTextNode(text);
    element.appendChild(textNode);
    object.appendChild(element);
}

function postActionMessage(data, target) {
    let key;
    if(data.error === 1) {
        key = "error";
    } else {
        key = "success"
    }
    addNotification(key, data.message, target);
}

function getHttpParams() {
    return {
        "host": window.location.hostname,
        "protocol": window.location.protocol
    }
}

function addNotificationWithTranslation(notifiKey, translationKey, target) {
    //lets send everything to backend and then just return, to have available on data object in callback
    getAjax("/views/ajaxgetranslation?trans-key=" + translationKey + "&notifi-key=" + notifiKey + "&target=" + target, addTrans)
}

function addTrans(data) {
    addNotification(data.notifi_key, data.translation, data.target);
}

function addNotification(type, text, target) {
    const notification = document.getElementById("notification-" + target);
    const alert = document.getElementById("alert-" + target);
    notification.innerHTML = text;
    if(type === "error") {
        alert.className = "alert alert-danger";
    } else if(type === "success") {
        alert.className = "alert alert-success";
    }

    $("#alert-" + target).show().delay(5000).fadeOut();
}

function resetOptionsAndPreserveFirstChild(selectObject, selected=true) {
    let child;
    for(let i = 0; i < selectObject.childNodes.length; i++) {
        if(selectObject.childNodes[i].nodeType !== Node.TEXT_NODE) {
            child = selectObject.childNodes[i];
            if(selected === true) {
                child.selected = true;
            }
            break;
        }
    }
    selectObject.innerHTML = "";
    selectObject.appendChild(child);
}