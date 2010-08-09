(function() {
  //
  // Public interface for error handlers
  //
  Errors = {};

  // Don't handle errors when the window is unloading
  Event.observe(window, 'beforeunload', function() {
    Errors.handle = Prototype.emptyFunction;
  });

  Errors.handle = function(exception) {
    var error = {
      exception:  exception,
      name:       exception.name,
      message:    exception.message,
      title:      exception.name+(exception.message ? ": "+exception.message : ""),
      fileName:   exception.fileName || location.href || "(unknown)",
      lineNumber: exception.lineNumber || "(unknown)",
      trace:      exception.stack
    };

    if (!error.trace) {
      error.trace = Errors.stack_trace(0);
    }

    if (Prototype.Browser.IE && !exception.fake) {
      var onerror_original = window.onerror;
      window.onerror = function(message, url, line) {
        window.onerror = onerror_original;

        Errors.handle({
          fake:       true,
          name:       error.name,
          message:    error.message || message,
          fileName:   url,
          lineNumber: line,
          stack:      error.trace
        });

        return false;
      };

      return;
    }

    if (typeof(window.error_handler) == 'function') {
      try {
        window.error_handler(error);
      } catch(e) {}
    } else {
      Errors.notify(error);
    }
  };

  // Send the error details back to the server
  Errors.notify = function(error) {
    try {
      new Ajax.Request('/errors/debug_client', {
        parameters: {
          message: error.title,
          file: error.fileName,
          line: error.lineNumber,
          trace: error.trace
        },
        skipResponders: true
      });

      return true;
    } catch(e) {
      alert(e.name+": "+e.message);
      return false;
    }
  };

  // Generate a stack trace
  Errors.stack_trace = function(level) {
    level = level || 1;

    var trace = "";

    var calls = 0;
    var caller = arguments.callee;
    while (caller && calls < 100) {
      try {
        if (!caller.caller || caller == caller.caller) {
          break;
        } else {
          caller = caller.caller;
          calls++;
        }

        if (calls <= level) {
          // Don't add callers up to the specified level
          continue;
        }

        var code = caller.toString();
        if (code.match(/\bfunction\s+([\w\.]+)\(/)) {
          trace += "\n"+RegExp.$1+"()\n";
        } else {
          trace += "(anonymous function)\n";
          trace += code.split("\n").join(' ').replace(/ +/g, ' ').strip().truncate(60).strip()+"\n\n";
        }
      } catch(e) {
        trace += "\n\n"+e.name+": "+e.message;
        break;
      }
    }

    return trace;
  };

  //
  // Helper functions taken from prototype.js
  //
  var slice = Array.prototype.slice;

  function update(array, args) {
    var arrayLength = array.length, length = args.length;
    while (length--) array[arrayLength + length] = args[length];
    return array;
  }

  function merge(array, args) {
    array = slice.call(array, 0);
    return update(array, args);
  }

  // Wrap a function in a try/catch block
  function catchErrors() {
    var __method = this, args = slice.call(arguments, 0);
    return function() {
      try {
        var a = merge(args, arguments);
        return __method.apply(this, a);
      } catch(exception) {
        if (location.protocol.match(/^https?:?$/i)) {
          Errors.handle(exception);
        }

        (function() {
          throw exception;
        }).defer();

        return false;
      }
    };
  }

  Object.extend(Function.prototype, { catchErrors: catchErrors });
})();
