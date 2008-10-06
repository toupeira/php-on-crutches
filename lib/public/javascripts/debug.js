var DebugToolbar = {
  init: function() {
    $$('#debug-toolbar span a').each(function(link) {
      link.onclick = DebugToolbar.toggle.bind(DebugToolbar, link);
    });
  },

  toggle: function(link) {
    var win = $(link.parentNode.previousSibling);
    if (win.visible()) {
      win.hide();
    } else {
      if (this.last) {
        this.last.hide();
      }
      this.last = win;
      win.clonePosition(link, {
        setTop: false,
        setWidth: false,
        setHeight: false,
        offsetLeft: -6
      });
      win.show();
    }

    return false;
  },

  open: function(link, win) {
    new Ajax.Request(link.href, {
      onSuccess: function(req) {
        if (req.responseText) {
          win.update(req.responseText);
        }
      },
      onFailure: function(req) {
        alert("Couldn't load data (Error "+req.status+")");
      }
    });
  }
};

Event.observe(window, 'load', DebugToolbar.init);
