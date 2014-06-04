<?php

class Search {
	
	public static function PrepareTSQuery($keywords)
	{
		App::import('Lib', 'SearchTransformer', array('file' => 'SearchTransformer.php'));
		App::import('Lib', 'SwsGoogleQueryParser', array('file' => 'SearchTransformer.php'));
		App::import('Lib', 'SwsGoogleQueryLexer', array('file' => 'SearchTransformer.php'));
		App::import('Lib', 'SwsTsqueryBuilder', array('file' => 'SearchTransformer.php'));
		$parser = new SwsGoogleQueryParser(new SwsGoogleQueryLexer());		
		$queryBuilder = new SwsTsqueryBuilder($parser->parse($keywords));
		
		$phrase = $queryBuilder->getResult();
		
		return $phrase;
		
/* OLD VERSION
 
 	    $words = explode(' ', $keywords);
	    
	    $str = '';
	    $i = 0;
	    foreach($words as $word)
	    {
	        if ($word != '')
	        {
//	        	$word = addcslashes($word, '\'');
//	        	$word = preg_replace('', $replacement, $subject);
	            if ($i) $str .= ' & ' . $word . ':*';
	            else $str = $word.':*';
	            $i++;	            
	        }
	    }
	    
	    return $str;
    */
		
	}
	
}