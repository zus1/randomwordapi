const tryTag = document.getElementById("tag");
const tryMinLength = document.getElementById("min-length");
const tryMaxLength = document.getElementById("max-length");
const tryWordsNum = document.getElementById("words-num");
const tryUrl = document.getElementById("try-url");
const tryResponse = document.getElementById("try-response");
const tryButton = document.getElementById("try-button");

tryButton.addEventListener("click", () => tryApi());

function tryApi() {
    const http = getHttpParams();
    let prefix = http.protocol + "//" + http.host;
    let endpoint = "/api/v1/generate?"

    if(tryTag.value !== "") {
        endpoint = endpoint + "&language=" + tryTag.value;
    }
    if(tryMinLength.value !== "") {
        endpoint = endpoint + "&min_length=" + tryMinLength.value;
    }
    if(tryMaxLength.value !== "") {
        endpoint = endpoint + "&max_length=" + tryMaxLength.value;
    }
    if(tryWordsNum.value !== "") {
        endpoint = endpoint + "&words_num=" + tryWordsNum.value;
    }

    endpoint = formatEndpoint(endpoint);
    tryUrl.innerHTML = prefix + endpoint;

    getAjax(endpoint, postTryApi, false)
}

function postTryApi(data) {
    tryResponse.style.color = "white";
    tryResponse.innerHTML = JSON.stringify(data, null, 2);
}

function formatEndpoint(endpoint) {
    const endpointParts = endpoint.split("?");
    if(endpointParts.length > 1) {
        endpointParts[1] = endpointParts[1].substring(1);
    }

    return endpointParts.join("?");
}