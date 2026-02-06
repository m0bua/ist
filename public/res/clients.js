
let get = new URLSearchParams((new URL(window.location.href)).search),
  timeout = get.has('t') ? get.get('t') : 1;

setTimeout(fill, 1);
setTimeout(changeIsActive, 10);

function changeIsActive() {
  var els = document.getElementsByClassName('isActive');
  Array.prototype.forEach.call(els, function (el) {
    el.onclick = function (event) {
      let xmlhttp = new XMLHttpRequest();
      xmlhttp.open("GET", "/?cfg=active&name=" + event.target.id, false);
      xmlhttp.send();
      fill(false);
    }
  })
}

function fill(repeat = true) {
  if (repeat) setTimeout(fill, timeout * 1000);
  let xmlhttp = new XMLHttpRequest(),
    tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
  xmlhttp.open("GET", "/?format=json&t=" + timeout + '&z=' + tz, false);
  xmlhttp.send();

  element(document.getElementById('body'), JSON.parse(xmlhttp.responseText));
}

function element(el, data, replace = true) {
  Array.prototype.forEach.call(data, function (item, key) {
    if (item.tag != undefined) {
      let exist = el.children[key] != undefined && el.children[key].tagName === item.tag.toUpperCase(),
        newEl = exist ? el.children[key] : document.createElement(item.tag);

      if (item.children != undefined) element(newEl, item.children, false);
      if (!exist || (item.text != undefined && newEl.innerText != item.text)) {
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
