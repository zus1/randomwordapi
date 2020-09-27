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

function getHttpParams() {
    return {
        "host": window.location.hostname,
        "protocol": window.location.protocol
    }
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