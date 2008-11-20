<?# $Id$ ?>
<?

   # Insert JavaScript code
   function javascript($code, array $options=null) {
      $code = "\n//<![CDATA[\n$code\n//]]>\n";
      return content_tag('script', $code, $options, array(
         'type' => 'text/javascript',
      )).N;
   }

   # Generate a link which executes JavaScript code
   function link_to_function($title, $function, $options=null) {
      $options['onclick'] = "$function; return false";
      return link_to($title, '#', $options);
   }

   # Genenerate a button which executes JavaScript code
   function button_to_function($title, $function, $options=null) {
      $options['onclick'] = "$function; return false";
      return button_tag($title, $options);
   }

   # Generate a link to toggle an element
   function toggle_link($title, $id=null, $options=null) {
      if ($id) {
         $onclick = "\$('$id').toggle(); return false";
      } else {
         $onclick = '$(this.nextSibling).toggle(); return false';
      }
      return link_to($title, '#', array_merge((array) $options, array('onclick' => $onclick)));
   }

?>
