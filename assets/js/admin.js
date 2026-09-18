document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[data-copy]').forEach(function(btn){
        btn.addEventListener('click',function(){
            var el=document.querySelector(btn.dataset.copy);
            if(!el)return;
            navigator.clipboard.writeText(el.textContent.trim()).then(function(){
                var old=btn.textContent;
                btn.textContent='✓';
                setTimeout(function(){btn.textContent=old;},900);
            });
        });
    });

    var qr=document.getElementById('s2fa-qrcode');
    if(qr && window.QRCode){
        var uri=qr.getAttribute('data-uri');
        if(uri){
            qr.innerHTML='';
            new QRCode(qr,{
                text:uri,
                width:220,
                height:220,
                colorDark:'#111827',
                colorLight:'#ffffff',
                correctLevel:QRCode.CorrectLevel.M
            });
        }
    }
});
