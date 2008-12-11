<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class Inflector
   {
      static protected $_plurals;
      static protected $_singulars;
      static protected $_uncountables;

      static function plural($rule, $replacement=null) {
         if (is_array($rule)) {
            foreach (array_reverse($rule) as $rule => $replacement) {
               self::plural($rule, $replacement);
            }
         } else {
            self::$_plurals[$rule] = $replacement;
         }
      }

      static function singular($rule, $replacement=null) {
         if (is_array($rule)) {
            foreach (array_reverse($rule) as $rule => $replacement) {
               self::singular($rule, $replacement);
            }
         } else {
            self::$_singulars[$rule] = $replacement;
         }
      }

      static function irregular($singular, $plural=null) {
         if (is_array($singular)) {
            foreach (array_reverse($singular) as $singular => $plural) {
               self::irregular($singular, $plural);
            }
         } elseif (mb_strtoupper($singular[0]) == mb_strtoupper($plural[0])) {
            self::plural(
               '#('.$singular[0].')'.mb_substr($singular, 1).'$#i',
               '\1'.mb_substr($plural, 1)
            );
            self::singular(
               '#('.$plural[0].')'.mb_substr($plural, 1).'$#i',
               '\1'.mb_substr($singular, 1)
            );
         } else {
            self::plural(
               '#'.mb_strtoupper($singular[0]).'(?i)'.mb_substr($singular, 1).'$#',
               ucfirst($plural)
            );
            self::plural(
               '#'.mb_strtolower($singular[0]).'(?i)'.mb_substr($singular, 1).'$#',
               mb_strtolower($plural[0]).mb_substr($plural, 1)
            );
            self::singular(
               '#'.mb_strtoupper($plural[0]).'(?i)'.mb_substr($plural, 1).'$#',
               ucfirst($singular)
            );
            self::singular(
               '#'.mb_strtolower($plural[0]).'(?i)'.mb_substr($plural, 1).'$#',
               mb_strtolower($singular[0]).mb_substr($singular, 1)
            );
         }
      }

      static function uncountable($words) {
         $words = (is_array($words) ? $words : func_get_args());
         self::$_uncountables = array_unique(array_merge(
            (array) self::$_uncountables,
            array_map(mb_strtolower, $words)
         ));
      }

      static function pluralize($word, $singular=null, $plural=null, $translate=true) {
         if ($singular and $plural) {
            $count = $word;
            if ($translate) {
               $word = ngettext($singular, $plural, $count);
            } else {
               $word = ($count == 1 ? $singular : $plural);
            }
            return sprintf("%d %s", $count, $word);
         } elseif ($singular) {
            return self::pluralize($word, $singular, self::pluralize($singular), $translate);
         } elseif (blank($word) or in_array(mb_strtolower($word), self::$_uncountables)) {
            return $word;
         } else {
            foreach (array_reverse(self::$_plurals) as $rule => $replacement) {
               if ($result = preg_replace($rule, $replacement, $word, -1, $match) and $match) {
                  return $result;
               }
            }

            return $word;
         }
      }

      static function singularize($word) {
         if (blank($word) or in_array(mb_strtolower($word), self::$_uncountables)) {
            return $word;
         } else {
            foreach (array_reverse(self::$_singulars) as $rule => $replacement) {
               if ($result = preg_replace($rule, $replacement, $word, -1, $match) and $match) {
                  return $result;
               }
            }

            return $word;
         }
      }

      static function camelize($word) {
         return str_replace(' ', '', ucwords(str_replace('_', ' ', $word)));
      }

      static function underscore($word) {
         return mb_strtolower(preg_replace('/[-\/\.\s]+/', '_',
                              preg_replace('/([a-z\d])([A-Z])/', '\1_\2',
                              preg_replace('/([A-Z]+)([A-Z][a-z])/', '\1_\2', $word))));
      }

      static function humanize($word, $translate=true) {
         $word = self::underscore($word);
         if (mb_substr($word, -3) == '_id') {
            $word = mb_substr($word, 0, -3);
         }

         $word = ucfirst(str_replace('_', ' ', $word));
         return $translate ? _($word) : $word;
      }

      static function titleize($word) {
         return ucwords(self::humanize($word));
      }

      static function dasherize($word) {
         return str_replace('_', '-', underscore($word));
      }

      static function parameterize($string, $sep='-') {
         $string = self::transliterate($string);

         $string = preg_replace('/[^\w_]+/', $sep, $string);
         $string = preg_replace("/^$sep|$sep$/i", '', $string);

         return mb_strtolower($string);
      }

      static function transliterate($string) {
         return str_replace('?', ' ', iconv('utf-8', 'ascii//translit', $string));
      }

      static function tableize($class) {
         return self::pluralize(self::underscore($class));
      }

      static function classify($class) {
         if (class_exists($class = self::camelize(self::singularize($class)))) {
            return $class;
         }
      }

      static function foreign_key($class, $sep='_') {
         return self::underscore($class).$sep.'id';
      }

      static function constantize($key) {
         if (defined($key)) {
            return constant($key);
         }
      }

      static function ordinalize($number) {
         $mod = $number % 100;
         if ($mod >= 11 and $mod <= 13) {
            return $number.'th';
         } else {
            switch ($number % 10) {
               case 1:  return $number.'st';
               case 2:  return $number.'nd';
               case 3:  return $number.'rd';
               default: return $number.'th';
            }
         }
      }
   }

   function pluralize($word, $singular=null, $plural=null, $translate=true) {
      return Inflector::pluralize($word, $singular, $plural, $translate);
   }

   function singularize($word) {
      return Inflector::singularize($word);
   }

   function camelize($word) {
      return Inflector::camelize($word);
   }

   function underscore($word) {
      return Inflector::underscore($word);
   }

   function humanize($word, $translate=true) {
      return Inflector::humanize($word, $translate);
   }

   function titleize($word) {
      return Inflector::titleize($word);
   }

   function dasherize($word) {
      return Inflector::dasherize($word);
   }

   function parameterize($word, $sep='-') {
      return Inflector::parameterize($word, $sep);
   }

   function transliterate($word) {
      return Inflector::transliterate($word);
   }

   function tableize($word) {
      return Inflector::tableize($word);
   }

   function classify($word) {
      return Inflector::classify($word);
   }

   function foreign_key($word, $sep='_') {
      return Inflector::foreign_key($word, $sep);
   }

   function constantize($word) {
      return Inflector::constantize($word);
   }

   function ordinalize($number) {
      return Inflector::ordinalize($number);
   }

   # Default inflections from Rails

   Inflector::plural(array(
      '/(quiz)$/i' => '\1zes',
      '/^(ox)$/i' => '\1en',
      '/([m|l])ouse$/i' => '\1ice',
      '/(matr|vert|ind)(?:ix|ex)$/i' => '\1ices',
      '/(x|ch|ss|sh)$/i' => '\1es',
      '/([^aeiouy]|qu)y$/i' => '\1ies',
      '/(hive)$/i' => '\1s',
      '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
      '/sis$/i' => 'ses',
      '/([ti])um$/i' => '\1a',
      '/(buffal|tomat)o$/i' => '\1oes',
      '/(bu)s$/i' => '\1ses',
      '/(alias|status)$/i' => '\1es',
      '/(octop|vir)us$/i' => '\1i',
      '/(ax|test)is$/i' => '\1es',
      '/s$/i' => 's',
      '/$/' => 's',
   ));

   Inflector::singular(array(
      '/(quiz)zes$/i' => '\1',
      '/(matr)ices$/i' => '\1ix',
      '/(vert|ind)ices$/i' => '\1ex',
      '/^(ox)en/i' => '\1',
      '/(alias|status)es$/i' => '\1',
      '/(octop|vir)i$/i' => '\1us',
      '/(cris|ax|test)es$/i' => '\1is',
      '/(shoe)s$/i' => '\1',
      '/(o)es$/i' => '\1',
      '/(bus)es$/i' => '\1',
      '/([m|l])ice$/i' => '\1ouse',
      '/(x|ch|ss|sh)es$/i' => '\1',
      '/(m)ovies$/i' => '\1ovie',
      '/(s)eries$/i' => '\1eries',
      '/([^aeiouy]|qu)ies$/i' => '\1y',
      '/([lr])ves$/i' => '\1f',
      '/(tive)s$/i' => '\1',
      '/(hive)s$/i' => '\1',
      '/([^f])ves$/i' => '\1fe',
      '/(^analy)ses$/i' => '\1sis',
      '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
      '/([ti])a$/i' => '\1um',
      '/(n)ews$/i' => '\1ews',
      '/(alias|status)$/i' => '\1',
      '/s$/i' => '',
   ));

   Inflector::irregular(array(
      'cow'    => 'kine',
      'move'   => 'moves',
      'sex'    => 'sexes',
      'child'  => 'children',
      'man'    => 'men',
      'person' => 'people',
   ));

   Inflector::uncountable(array(
      'sheep',
      'fish',
      'series',
      'species',
      'money',
      'rice',
      'information',
      'equipment',
   ));

?>
