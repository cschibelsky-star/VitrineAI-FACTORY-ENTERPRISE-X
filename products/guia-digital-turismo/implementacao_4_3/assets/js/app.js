document.addEventListener('DOMContentLoaded',()=>{
  // Busca local instantânea
  document.querySelectorAll('[data-search]').forEach(input=>{
    input.addEventListener('input',()=>{
      const q=normalize(input.value);
      document.querySelectorAll('[data-card], .event-row, .commercial-card, .place-card').forEach(card=>{
        const t=normalize(card.innerText);
        card.style.display=t.includes(q)?'':'none';
      });
    });
  });

  // Service Worker com atualização controlada do PWA instalado
  if('serviceWorker' in navigator){
    let refreshing=false;
    navigator.serviceWorker.addEventListener('controllerchange',()=>{
      if(refreshing) return;
      refreshing=true;
      window.location.reload();
    });

    navigator.serviceWorker.register('service-worker.js', {updateViaCache:'none'}).then(reg=>{
      reg.update().catch(()=>{});
      reg.addEventListener('updatefound',()=>{
        const worker=reg.installing;
        if(!worker) return;
        worker.addEventListener('statechange',()=>{
          if(worker.state==='installed' && navigator.serviceWorker.controller){
            showUpdateNotice(worker);
          }
        });
      });
    }).catch(()=>{});
  }

  // Pré-carregamento de páginas principais quando o navegador estiver livre
  const corePages=['app.php','atrativos.php','eventos.php','guia-comercial.php','mapa.php','favoritos.php','perfil.php'];
  const warmup=()=>corePages.forEach(prefetchUrl);
  if('requestIdleCallback' in window){ requestIdleCallback(warmup,{timeout:1800}); }
  else { setTimeout(warmup,900); }

  // Pré-carrega link no primeiro toque/mouseover: reduz demora percebida ao clicar
  document.querySelectorAll('a[href]').forEach(link=>{
    ['touchstart','mouseenter','focus'].forEach(evt=>{
      link.addEventListener(evt,()=>prefetchUrl(link.href),{once:true,passive:true});
    });
    link.addEventListener('click',()=>{
      const url=new URL(link.href,location.href);
      if(url.origin===location.origin && !url.pathname.includes('/admin/') && !link.target){
        document.body.classList.add('is-navigating');
      }
    });
  });
});

function normalize(value){
  return String(value||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'');
}

const prefetched=new Set();
function prefetchUrl(input){
  try{
    const url=new URL(input,location.href);
    if(url.origin!==location.origin) return;
    if(url.pathname.includes('/admin/')) return;
    if(prefetched.has(url.href)) return;
    prefetched.add(url.href);
    fetch(url.href,{method:'GET',credentials:'same-origin',cache:'force-cache'}).catch(()=>{});
  }catch(e){}
}

function shareItem(title){
  const data={title:title,text:'Conheça '+title+' no Conheça Sumaré',url:location.href};
  if(navigator.share){navigator.share(data)}
  else{navigator.clipboard&&navigator.clipboard.writeText(location.href);alert('Link copiado.');}
}
function favoriteItem(id){
  let fav=JSON.parse(localStorage.getItem('visite_sumare_fav')||'[]');
  if(!fav.includes(id)) fav.push(id);
  localStorage.setItem('visite_sumare_fav',JSON.stringify(fav));
  alert('Salvo nos favoritos deste dispositivo.');
}


function showUpdateNotice(worker){
  if(document.querySelector('.update-toast')) return;
  const toast=document.createElement('div');
  toast.className='update-toast';
  toast.innerHTML='<span>Nova versão disponível.</span><button type="button">Atualizar</button>';
  document.body.appendChild(toast);
  const btn=toast.querySelector('button');
  btn.addEventListener('click',()=>{
    if(worker && worker.postMessage){
      worker.postMessage({type:'SKIP_WAITING'});
    }else{
      window.location.reload();
    }
  });
}

function toggleFavorite(id){
  return favoriteItem(id);
}

function getFavorites(){
  try { return JSON.parse(localStorage.getItem('visite_sumare_fav')||'[]'); }
  catch(e){ return []; }
}

function clearFavorites(){
  localStorage.removeItem('visite_sumare_fav');
  location.reload();
}

function renderFavorites(){
  const container=document.querySelector('[data-favorites-list]');
  if(!container) return;
  const fav=getFavorites();
  if(!fav.length){
    container.innerHTML='<div class="empty">Nenhum favorito salvo neste dispositivo.</div>';
    return;
  }
  container.innerHTML=fav.map(id=>{
    const clean=String(id).replace('atrativo-','').replace('evento_','');
    const isEvent=String(id).startsWith('evento_');
    const url=isEvent?'eventos.php?id='+encodeURIComponent(clean):'atrativos.php?id='+encodeURIComponent(clean);
    const label=isEvent?'Evento salvo':'Atrativo salvo';
    return '<a class="event-row" href="'+url+'"><div class="date-tile">♡</div><div><h3>'+label+'</h3><p>'+clean+'</p></div><span></span></a>';
  }).join('');
}

document.addEventListener('DOMContentLoaded',renderFavorites);
