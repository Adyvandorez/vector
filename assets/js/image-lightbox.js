
(function(){
'use strict';

let modal,img,caption;

function init(){
 if(modal)return;
 modal=document.createElement('div');
 modal.className='vi-lightbox';
 modal.innerHTML=`
 <div class="vi-lightbox__dialog">
  <div class="vi-lightbox__stage">
   <div class="vi-lightbox__loading">Memuat gambar...</div>
   <img class="vi-lightbox__image">
  </div>
  <div class="vi-lightbox__caption"></div>
  <button class="vi-lightbox__close">&times;</button>
 </div>`;
 document.body.appendChild(modal);
 img=modal.querySelector('.vi-lightbox__image');
 caption=modal.querySelector('.vi-lightbox__caption');

 modal.querySelector('.vi-lightbox__close').onclick=close;
 modal.onclick=e=>{if(e.target===modal)close()};
 document.addEventListener('keydown',e=>{
   if(e.key==='Escape')close();
 });
}

function open(el){
 init();
 let src=el.dataset.preview || el.href || el.querySelector('img')?.src;
 if(!src)return;
 img.style.display='none';
 modal.classList.add('is-open');
 caption.textContent=el.dataset.caption || '';
 img.onload=()=>img.style.display='block';
 img.src=src;
}

function close(){
 if(modal)modal.classList.remove('is-open');
}

document.addEventListener('click',function(e){
 let target=e.target.closest('.vi-image-trigger,[data-preview]');
 if(target){
   e.preventDefault();
   open(target);
 }
});

window.VIPreview={open,close};
})();
