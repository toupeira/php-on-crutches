(function() {
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
        if (window.error_handler && window.error_handler.disabled) {
          throw exception;
        }

        var error = {
          exception:  exception,
          name:       exception.name,
          message:    exception.message,
          title:      exception.name+(exception.message ? ": "+exception.message : ""),
          fileName:   exception.fileName || "(unknown)",
          lineNumber: exception.lineNumber || "(unknown)",
          trace:      exception.stack
        };

        if (!error.trace) {
          error.trace = Errors.stack_trace();
        }

        if (typeof(window.error_handler) == 'function') {
          try {
            window.error_handler(error);
          } catch(e) {}
        } else {
          Errors.notify(error);
        }

        return false;
      }
    };
  }

  Object.extend(Function.prototype, { catchErrors: catchErrors });

  // Public interface for error handlers
  Errors = {};

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
  Errors.stack_trace = function() {
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

        if (calls <= 1) {
          // Don't add ourself or our caller
          continue;
        }

        var code = caller.toString();
        if (code.match(/\bfunction\s+([\w\.]+)\(/)) {
          trace += RegExp.$1+"()\n";
        } else {
          trace += "(anonymous function)\n";
          trace += code.split("\n").slice(1, 3).join("\n")+"\n\n";
        }
      } catch(e) {
        trace += "\n\nError: "+e;
        break;
      }
    }

    return trace;
  };
})();
