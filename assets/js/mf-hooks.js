/* РосТур Эксперт: хуки вовлечения для Метрики 91421507, 1 раз за сессию */
(function(){
  var CID=91421507;
  function once(id){
    try{ if(sessionStorage.getItem('mf_'+id)) return; sessionStorage.setItem('mf_'+id,'1'); }catch(e){}
    if(window.ym){ ym(CID,'reachGoal',id); }
  }
  document.addEventListener('click',function(e){
    var t=e.target; if(!t||!t.closest) return;
    if(t.closest('#anyaBtn,.anya-btn')) once('anya_open');
    if(t.closest('[id$="-tariff-wd"],[id$="-tariff-we"],[id$="-days"],[id$="-people"]')) once('calc_used');
    if(t.closest('a[href="#raschet"],a[href="#calc"]')) once('cta_calc');
    if(t.closest('a[href="#zayavka"]')) once('cta_book');
  },true);
  document.addEventListener('submit',function(e){
    if(e.target && e.target.id==='anyaForm') once('anya_lead');
  },true);
})();
