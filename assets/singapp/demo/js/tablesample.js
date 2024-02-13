$(function(){
  console.log('table sample page');
  function pageLoad(){
      console.log('table sample page load');
      $('.widget').widgster();
      $('.sparkline').each(function(){
          $(this).sparkline('html',$(this).data());
      });
      $('.js-progress-animate').animateProgressBar();
  }
  pageLoad();
  SingApp.onPageLoad(pageLoad);
});