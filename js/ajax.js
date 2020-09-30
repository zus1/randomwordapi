function postAjax(endpoint, data, callback) {
    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log(this.responseText);
            callback(JSON.parse(this.responseText));
        }
    };

    const http = getHttpParams();
    xhttp.open("POST", http.protocol + "//" + http.host + endpoint, true);
    xhttp.send(data);
}

function getAjax(endpoint, callback)  {
    let xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            console.log(this.responseText);
            callback(JSON.parse(this.responseText));
        }
    };

    const http = getHttpParams();
    xhttp.open("GET", http.protocol + "//" + http.host + endpoint, true);
    xhttp.send();
}