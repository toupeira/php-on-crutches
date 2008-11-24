var DebugToolbar = {
  init: function() {
    $$('#debug-toolbar > div > a').each(function(link) {
      link.onclick = DebugToolbar.toggle.bind(DebugToolbar, link);
    });

    DebugToolbar.reset = DebugToolbar.reset.bindAsEventListener(DebugToolbar);
  },

  toggle: function(link, show) {
    var win = link.next('div');
    var shadow = $('debug-panel-shadow');

    if (show === undefined || typeof(show) == 'object') {
      show = (win.style.display != 'block');
    }

    if (show) {
      if (this.last) {
        this.toggle(this.last, false);
      }
      this.last = link;

      win.clonePosition(link, {
        setTop: false,
        setWidth: false,
        setHeight: false
      });
      win.style.display = 'block';

      link.addClassName('active');

      shadow.style.width = win.getWidth() + 6 + 'px';
      shadow.style.height = win.getHeight() + 6 + 'px';
      shadow.style.left = win.offsetLeft - 3 + 'px';
      shadow.style.display = 'block';

      document.observe('click', this.reset);
    } else {
      shadow.hide();
      link.removeClassName('active');
      win.hide();
      document.stopObserving('click', this.reset);
    }

    return false;
  },

  reset: function(event) {
    if (!Event.element(event).descendantOf('debug-toolbar') && this.last) {
      this.toggle(this.last, false);
    }
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

document.observe('dom:loaded', DebugToolbar.init);
