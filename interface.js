function handleRequest(){var e=document.getElementById("method"),t=document.getElementById("resource"),n=document.getElementById("jsonInput"),e=e.value,t=t.value,n=n.value,t="/~sgjkattu/v1/rest.php?resource="+(t||"");const s=new XMLHttpRequest;s.open(e,t,!0),s.setRequestHeader("Content-Type","application/json"),s.onreadystatechange=function(){var e,t;s.readyState===XMLHttpRequest.DONE&&(e=s.status,t=s.responseText,displayStatus(e),displayResponse(t))},"POST"===e||"PATCH"===e?s.send(n):s.send()}function displayStatus(e){document.getElementById("status").textContent=e}function displayResponse(e){document.getElementById("response").textContent=e}function clearDisplay(){displayStatus(""),displayResponse("")}document.addEventListener("DOMContentLoaded",function(){var e=document.getElementById("sendRequest"),t=document.getElementById("clearResponse");e.addEventListener("click",handleRequest),t.addEventListener("click",clearDisplay)});
