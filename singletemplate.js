require(['jquery'], $ => {
   $(document).ready(() => {
       const $stampactiondivs = $('.datafield_datestamp-stampaction');
       for (let s = 0; s < $stampactiondivs.length; s++) {
           const $stampactiondiv = $($stampactiondivs[s]);

           if (!$stampactiondiv.find('form').length) {
               const $stampactionform = $('<form>')
                   .attr('method', $stampactiondiv.attr('data-method'))
                   .attr('action', $stampactiondiv.attr('data-action'))
                   .attr('enctype', $stampactiondiv.attr('data-enctype'))
                   .attr('style', $stampactiondiv.attr('style'))
                   .html($stampactiondiv.html());
               $stampactionform.insertAfter($stampactiondiv);
               $stampactiondiv.remove();
           }
       }
   });
});
