function addNotificationWithTranslation(notifiKey, translationKey, target) {
    //lets send everything to backend and then just return, to have available on data object in callback
    getAjax("/views/ajaxgetranslation?trans-key=" + translationKey + "&notifi-key=" + notifiKey + "&target=" + target, addTrans)
}

function addTrans(data) {
    addNotification(data.notifi_key, data.translation, data.target);
}