<?php
include '../bootstrap.php';

if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo Helper::getDataJson();
    exit;
}

?>

<head>
    <style>
        body {
            background-color: #161616;
            height: 100%;
            margin: 0;
            padding: 0;
            color: lightgray;
            top: 0;
        }

        table {
            width: 80%;
            margin: 0 10%;
        }

        table,
        th,
        td {
            border: 1px solid #646464;
            border-collapse: collapse;
        }

        th {
            font-weight: bolder;
        }

        th,
        td {
            padding: 1em;
            text-align: center;
        }

        br{
            font-size: 1.7em;
        }
    </style>
</head>

<body id="body"></body>

<script>
    let get = new URLSearchParams((new URL(window.location.href)).search),
        timeout = get.has('t') ? get.get('t') : 1;

    fill()

    function fill() {
        setTimeout(fill, timeout * 1000);
        let xmlhttp = new XMLHttpRequest();
        xmlhttp.open("GET", "/?format=json", false);
        xmlhttp.send();

        element(document.getElementById('body'), JSON.parse(xmlhttp.responseText));
    }

    function element(el, data, replace = true) {
        Array.prototype.forEach.call(data, function(item, key) {
            if (item.tag != undefined) {
                let exist = el.children[key] != undefined && el.children[key].tagName === item.tag.toUpperCase(),
                    newEl = exist ? el.children[key] : document.createElement(item.tag);

                if (item.children != undefined) element(newEl, item.children, false);
                if (!exist || (item.upd != undefined && item.upd === true)) {
                    if (item.text != undefined) newEl.innerText = item.text;
                    if (item.params != undefined)
                        for (const [key, val] of Object.entries(item.params)) {
                            let resVal = '';
                            if (key === 'style')
                                for (const [sKey, sVal] of Object.entries(val))
                                    resVal += sKey + ':' + sVal + ';';
                            else resVal = val;
                            newEl.setAttribute(key, resVal);
                        }
                    if (!exist) {
                        if (replace) el.innerHTML = '<br>';
                        el.appendChild(newEl);
                    }
                }
            }
        });
    }
</script>
