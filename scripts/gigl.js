var checkout_url = window.location.href;
var gigl_url = 'https://gigl.pushtechn.com';

urlParts = /^(?:\w+\:\/\/)?([^\/]+)([^\?]*)\??(.*)$/.exec(checkout_url);
hostname = urlParts[1]; // www.example.com
path = urlParts[2];

if (path.includes("/orders/") || path.includes("/checkouts/cn/")) {
    const getsplitToken = path.split('/');
    const apiUrl = `${gigl_url}/gigl-delivery-shipping/get_track_id.php?site_url=${hostname}&token=${getsplitToken[3]}`;
    console.log(apiUrl);
    
    if(path.includes("/checkouts/cn/")){
        var startTime = new Date().getTime();
        var intervalID = setInterval(function() { 
            var xhr = new XMLHttpRequest();
        xhr.open('GET', apiUrl, true);
        //xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var responseData = JSON.parse( xhr.responseText);
                console.log(responseData);
                if(responseData.waybill){
                    document.querySelector('.btn-gigl').style.display = "inline-block";
                }
                var shipment_data = `
                <p><span class="title-text">Origin</span>: ${responseData.tracking.Object.Origin}</p><hr></hr>
                <p><span class="title-text">Waybill</span>: ${responseData.waybill}</p><hr></hr>
                <p><span class="title-text">Destination</span>: ${responseData.tracking.Object.Destination}</p><hr></hr>
                <p><span class="title-text">MobileShipmentTrackings</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].MobileShipmentTrackingId}</p><hr></hr>
                <p><span class="title-text">Status</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].Status}</p><hr></hr>
                <p><span class="title-text">DateTime</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].DateTime}</p><hr></hr>
                <p><span class="title-text">TrackingType</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].TrackingType}</p>`;
                document.querySelector('.shipment-status').innerHTML = shipment_data;
            } else {
                console.error('Request failed with status:', xhr.status);
            }
        };
    
        xhr.onerror = function() {
            console.error('Network error occurred');
        };
    
        xhr.send();
            if(new Date().getTime() - startTime > 60000){
                clearInterval(intervalID);
                return;
            }
        }, 13000);
    }else{
        var xhr = new XMLHttpRequest();
        xhr.open('GET', apiUrl, true);
        //xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (xhr.status === 200) {
                var responseData = JSON.parse( xhr.responseText);
                console.log(responseData);
                if(responseData.waybill){
                    document.querySelector('.btn-gigl').style.display = "inline-block";
                }
                var shipment_data = `
                <p><span class="title-text">Origin</span>: ${responseData.tracking.Object.Origin}</p><hr></hr>
                <p><span class="title-text">Waybill</span>: ${responseData.waybill}</p><hr></hr>
                <p><span class="title-text">Destination</span>: ${responseData.tracking.Object.Destination}</p><hr></hr>
                <p><span class="title-text">MobileShipmentTrackings</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].MobileShipmentTrackingId}</p><hr></hr>
                <p><span class="title-text">Status</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].Status}</p><hr></hr>
                <p><span class="title-text">DateTime</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].DateTime}</p><hr></hr>
                <p><span class="title-text">TrackingType</span>: ${responseData.tracking.Object.MobileShipmentTrackings[0].TrackingType}</p>`;
                document.querySelector('.shipment-status').innerHTML = shipment_data;
            } else {
                console.error('Request failed with status:', xhr.status);
            }
        };
    
        xhr.onerror = function() {
            console.error('Network error occurred');
        };
    
        xhr.send();
    }
    
    // var customButton = document.createElement('button');
    // customButton.innerHTML = 'Track Shipping...';

    // customButton.style.backgroundColor = 'green';
    // customButton.style.color = 'white';
    // customButton.style.padding = '10px';
    // customButton.setAttribute('data-dismiss', 'modal');
    // customButton.setAttribute('class', 'new-btn');

    // document.body.prepend(customButton);

    // customButton.addEventListener('click', function() {
    //     alert('Button clicked!');
    // });

    var newhtml = `<style>
    * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Inter", sans-serif;
    }
    .title-text{
        font-weight:500;
        color:#000000;
    }
    body {
      /*display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #222;
      position: relative;
      min-height: 100vh;*/
    }
    .modal {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 0.4rem;
      width: 450px;
      padding: 1.3rem;
      min-height: 250px;
      position: absolute;
      top: 20%;
      background-color: white;
      border: 1px solid #ddd;
      border-radius: 15px;
      visibility: visible;
      height: auto;
    }
    
    .modal .flex {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .modal input {
      padding: 0.7rem 1rem;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-size: 0.9em;
    }
    
    .modal p {
      font-size: 0.9rem;
      color: #777;
      margin: 0.4rem 0 0.2rem;
    }
    
    button {
      cursor: pointer;
      border: none;
      font-weight: 600;
    }
    
    .btn-gigl {
      display: inline-block;
      padding: 0.8rem 1.4rem;
      font-weight: 700;
      color: #000000;
      text-align: center;
      font-size: 1em;
    }
    
    .btn-open {
      position: absolute;
      top: 2px;
      right:10%;
    }
    
    .btn-close {
      transform: translate(10px, -20px);
      padding: 0.5rem 0.7rem;
      background: #eee;
      border-radius: 50%;
    }
    .overlay {
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      right: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(3px);
      z-index: 1;
    }
    .modal {
      z-index: 2;
    }
    .hidden {
      display: none;
    }
    .center-tracking{
        align-items: center;
    }
    .shipment-status{
        padding-top:30px;
    }
    .gigl-loader {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid blue;
  border-right: 16px solid green;
  border-bottom: 16px solid red;
  width: 60px;
  height: 60px;
  -webkit-animation: spin 2s linear infinite;
  animation: spin 2s linear infinite;
}

@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}</style><section class="modal hidden">
      <div class="flex">
        <img src="https://giglogistics.com/static/media/logo.1c67b3c3.png" width="50px" height="50px" alt="user" />
        <button class="btn-close">⨉</button>
      </div>
      <div>
        <h3>Stay in touch</h3>
        <p>
          <i>Your shipment is closer. :)</i>
        </p>
        <div class="shipment-status"><div class="gigl-loader"></div></div>
      </div>
    </section>
    
    <div class="overlay hidden"></div>
    <button class="btn-gigl btn-open" style="display:none;">track shiping <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 122.88 99.36" style="enable-background:new 0 0 122.88 99.36; width:50px;" xml:space="preserve"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;}</style><g><path class="st0" d="M78.29,23.33h18.44c5.52,0,4.23-0.66,7.33,3.93l15.53,22.97c3.25,4.81,3.3,3.77,3.3,9.54v18.99 c0,6.15-5.03,11.19-11.19,11.19h-2.28c0.2-0.99,0.3-2.02,0.3-3.07c0-8.77-7.11-15.89-15.89-15.89c-8.77,0-15.89,7.11-15.89,15.89 c0,1.05,0.1,2.07,0.3,3.07H58.14c0.19-0.99,0.3-2.02,0.3-3.07c0-8.77-7.11-15.89-15.89-15.89c-8.77,0-15.89,7.11-15.89,15.89 c0,1.05,0.1,2.07,0.3,3.07h-2.65c-5.66,0-10.29-4.63-10.29-10.29V63.05h64.27V23.33L78.29,23.33z M93.82,74.39 c6.89,0,12.48,5.59,12.48,12.49c0,6.89-5.59,12.48-12.48,12.48c-6.9,0-12.49-5.59-12.49-12.48C81.33,79.98,86.92,74.39,93.82,74.39 L93.82,74.39z M42.54,74.39c6.9,0,12.49,5.59,12.49,12.49c0,6.89-5.59,12.48-12.49,12.48c-6.89,0-12.48-5.59-12.48-12.48 C30.06,79.98,35.65,74.39,42.54,74.39L42.54,74.39z M42.54,83.18c2.04,0,3.7,1.65,3.7,3.7c0,2.04-1.65,3.69-3.7,3.69 c-2.04,0-3.69-1.66-3.69-3.69C38.85,84.83,40.51,83.18,42.54,83.18L42.54,83.18z M93.82,83.09c2.09,0,3.79,1.7,3.79,3.79 c0,2.09-1.7,3.79-3.79,3.79c-2.09,0-3.79-1.7-3.79-3.79C90.03,84.78,91.73,83.09,93.82,83.09L93.82,83.09z M89.01,32.35h3.55 l15.16,21.12v6.14c0,1.49-1.22,2.71-2.71,2.71h-16c-1.53,0-2.77-1.25-2.77-2.77V35.13C86.23,33.6,87.48,32.35,89.01,32.35 L89.01,32.35z M5.6,0h64.26c3.08,0,5.6,2.52,5.6,5.6v48.92c0,3.08-2.52,5.6-5.6,5.6H5.6c-3.08,0-5.6-2.52-5.6-5.6V5.6 C0,2.52,2.52,0,5.6,0L5.6,0z"></path></g></svg></button>`;
    
    if(document.querySelector('.modal') == null){
        var tempContainer = document.createElement('div');
        tempContainer.style.display = 'contents';
        tempContainer.innerHTML = newhtml;
        document.body.prepend(tempContainer);
        
        const modal = document.querySelector(".modal");
        const overlay = document.querySelector(".overlay");
        const openModalBtn = document.querySelector(".btn-open");
        const closeModalBtn = document.querySelector(".btn-close");

        //const modal = jQuery(".modal");
        //const overlay = jQuery(".overlay");
        //const openModalBtn = jQuery(".btn-open");
        //const closeModalBtn = jQuery(".btn-close");
        const bodyElement = document.querySelector("body");
        const openModal = function () {
          modal.classList.remove("hidden");
          overlay.classList.remove("hidden");
          bodyElement.classList.add("center-tracking");
        };
        // const openModal = function () {
        //   modal.removeClass("hidden");
        //   overlay.removeClass("hidden");
          
        // }
        openModalBtn.addEventListener("click", openModal);
          //openModalBtn.click(openModal);
        const closeModal = function () {
          modal.classList.add("hidden");
          overlay.classList.add("hidden");
          bodyElement.classList.remove("center-tracking")
        };
        //   const closeModal = function () {
        //   modal.addClass("hidden");
        //   overlay.addClass("hidden");
        //   
        // };
        closeModalBtn.addEventListener("click", closeModal);
        //closeModalBtn.click(closeModal);
        overlay.addEventListener("click", closeModal);
        //overlay.click(closeModal);
    }
    
}




