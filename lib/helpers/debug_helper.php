<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Dump an exception with backtrace.
   # Returns the formatted string.
   function dump_error($exception) {
      return "<h1>".titleize(get_class($exception))."</h1>".N
            . "<p>".$exception->getMessage()."</p>".N
            . "<pre>".$exception->getTraceAsString()."</pre>";
   }

?>
