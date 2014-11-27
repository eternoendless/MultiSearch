var MultiSearch = {
  
  currentProvider: 'google',
  
  lastSearch: {
    provider: '',
    query: '',
    page: 1
  },
  
  search: function(provider, page) {
    var self = this;
    var query = $('#searchBox').val();
    
    if (
      query.length
      && (
        self.currentProvider != self.lastSearch.provider
        || query != self.lastSearch.query
        || (page || 1) != self.lastSearch.page
      )
    ) {
      $('#searchResults')
      .html("Searching...")
      .load("index.php/search/"+provider+"?q="+encodeURIComponent(query)+"&page="+page, function(response, status, xhr) {
        if (status == 'error') {
          $(this).html(
            '<div class="alert alert-danger" role="alert">'
            +'<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>'
            +'<span class="sr-only">Error:</span> '
            +response
            +'</div>'
          );
        }
      });
    }
    
    $.extend(self.lastSearch, {
      provider: provider,
      query: query,
      page: page
    });
  },
  
  init: function() {
    var self = this;
    
    // initialize tabs
    $('#searchTabs a').click(function (e) {
      e.preventDefault()
      var item = $(this);
      
      self.currentProvider = item.data('provider');
      self.search(self.currentProvider, 1);
      
      item.tab('show');
    });
    
    // bind search
    $('form').submit(function(e){
      self.search(self.currentProvider, 1);
      return false;
    });
    
    // bind pagination
    $('#searchResults').delegate('.pager a', 'click', function(e){
      console.log(this);
      var page = self.lastSearch.page;
      switch ($(this).data('move')) {
        case 'next':
          page++; break;
        case 'previous':
          page--; break;
        default:
          page = 1;
      }
      self.search(self.currentProvider, page);
      e.preventDefault();
    });
  }
  
};

$().ready(function() {
  
  MultiSearch.init();
  
});