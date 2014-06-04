<?php

interface ISwsLexer
{
    public function execute( $str );
    public function getTokens();
}

interface ISwsParser
{
    public function __construct( ISwsLexer $lexer );
    public function parse( $input );
    public function addToken( $token );
}

class SwsGoogleQueryLexer implements ISwsLexer
{
	const QUOTE = '"';
	
	const PAROPEN = '(';
	const PARCLOSE = ')';		
	
    private $tokenStack = array();

    public function execute( $str )
    {
    	// "O'hara airport" O'Leare OR (MacBeth AND Farg"sasa")
		$str = preg_replace('/[\s]+/', ' ', $str);
		$parts = preg_split('/([\"() &|!])/', $str, NULL, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
				
		$quote = FALSE;
		$builder = '';
		
		$this->tokenStack = array();
		
		foreach($parts as $part) {
			if ($part == self::QUOTE) {
				if ($quote) {
					$this->tokenStack[] = trim($builder);
					
					$quote = FALSE;					
					$builder = '';				
				} 
				else {
					$quote = TRUE;
					$builder = '';
				}												
			}
			else {			
				$part = trim($part);
				
				if ($part !== '') {
					if ($quote) {
						$builder .= $part . ' ';
					}
					else {
						if (strcasecmp('OR', $part) === 0) {
							$part = '|';
						}
						elseif (strcasecmp('AND', $part) === 0) {
							$part = '&';
						}
						elseif (strcasecmp('NOT', $part) === 0) {
							$part = '!';
						}						
						
						$this->tokenStack[] = $part;				
					}
				}				
			}																	
		}
    }

    public function getTokens()
    {
    	return $this->tokenStack;
    }
}

class SwsGoogleQueryParser implements ISwsParser
{
    protected $lexer;

    public function __construct( ISwsLexer $lexer )
    {
    	$this->lexer = $lexer;
    }

    public function addToken( $token )
    {
    	$this->tokenStack[] = $token;
    }

    public function parse( $input )
    {
    	$this->lexer->execute( $input );
    	$tokens = $this->lexer->getTokens();

    	$expression = new SwsQueryExpression();

    	foreach ( $tokens as $token )
    	{
    		$expression = $this->processToken( $token, $expression );
    	}
				
		while ($par = $expression->getParentExpression()) {
			$expression = $par;
		}
		
		return $expression;
    }

    protected function processToken( $token, SwsQueryExpression $expression )
    {
		
    	switch ( $token )
    	{
    		case '(':
    			return $expression->addSubExpression();
    			break;
    		case ')':
				$par = $expression->getParentExpression();				
				if ($par) return $par;
				
    			break;
				
			case '!': 
				$expression->toggleCurrentBindingNegate();
				
				break;
			
			case '|': 
				$expression->setCurrentBindingMode(SwsQueryPart::MODE_OR);
				
				break;
			
			case '&': 
				$expression->setCurrentBindingMode(SwsQueryPart::MODE_AND);
				
				break;
    		default:
    			$modifier	= mb_substr( $token, 0, 1 );
    			$phrase		= mb_substr( trim($token), 1 );
    			
    			if (strlen($phrase) > 0)
    			{
	    			switch ( $modifier )
	    			{
	    				case '-':
	    					$expression->addPhrase($phrase, SwsQueryPart::MODE_AND, TRUE); // AND ! = exclude
	    					break;
						
	    				case '+':
	    					$expression->addPhrase($phrase, SwsQueryPart::MODE_AND, FALSE); // AND = include
	    					break;
						
	    				default:
	    					$expression->addPhrase($token);    					
	    			}
    			}
    	}
    	return $expression;
    }
}

abstract class SwsQueryPart {
	const MODE_DEFAULT = 1;
    const MODE_OR = 2;
    const MODE_AND = 3;
    
	protected $mode;
	protected $negate = FALSE;
	
	public function getMode()
    {
    	return $this->mode;
    }
	
	public function getNegate()
    {
    	return $this->negate;
    }
}

class SwsQueryExpression extends SwsQueryPart
{
	protected $currentMode = SwsQueryPart::MODE_DEFAULT;
	protected $currentNegate = FALSE;
	
    protected $parts = array(); // phrases and subs
    protected $parent;

    public function __construct($parent = NULL, $mode=SwsQueryPart::MODE_DEFAULT, $negate = FALSE)
    {
    	$this->parent = $parent;
		$this->mode = $mode;
		$this->negate = $negate;
    }

    public function addSubExpression($mode = FALSE, $negate = NULL)
    {
		if (empty($mode)) {
			$mode = $this->currentMode;
		}
		
		if (is_null($negate)) {
			$negate = $this->currentNegate;
		}
		
    	$this->addQuerySubExpression($expression = new self($this, $mode, $negate));
		$this->clearCurrentBindingNegate();
		
    	return $expression;
    }
	
	public function addQuerySubExpression(SwsQueryExpression $expression)
    {
		$this->parts[] = $expression;
	}
    
	public function setCurrentBindingMode($mode) {
		$this->currentMode = $mode;
	}
	
	public function clearCurrentBindingNegate() {
		$this->currentNegate = FALSE;
	}
	
	public function toggleCurrentBindingNegate() {
		$this->currentNegate = !$this->currentNegate;
	}
 	
    public function getParts()
    {
    	return $this->parts;
    }

    public function getParentExpression()
    {
    	return $this->parent;
    }

    protected function addQueryPhrase(SwsQueryPhrase $phrase)
    {
    	$this->parts[] = $phrase;
    }

    public function addPhrase($input, $mode = FALSE, $negate = NULL)
    {
		if (empty($mode)) {
			$mode = $this->currentMode;
		}
		
		if (is_null($negate)) {
			$negate = $this->currentNegate;
		}
		
    	$this->addQueryPhrase(new SwsQueryPhrase($input , $mode, $negate));
		$this->clearCurrentBindingNegate();
    }    
}
 
class SwsQueryPhrase extends SwsQueryPart
{    
    protected $phrase;	
    
    public function __construct( $input, $mode=self::MODE_DEFAULT, $negate = FALSE)
    {
    	$this->phrase = $input;
    	$this->mode = $mode;
		$this->negate = $negate;
    }

    public function __toString()
    {
    	return $this->phrase;
    }
}

class SwsTsqueryBuilder
{
    protected $expression;
    protected $query;

    public function __construct( SwsQueryExpression $expression )
    {
    	$this->query = $this->trimQuery($this->processExpression($expression));		
    }
	
	public function trimQuery($str) {
		return trim($str, ' &|' );		
	}

    public function getResult()
    {
    	return $this->query;
    }

    protected function processExpression( SwsQueryExpression $expression )
    {
    	$query = '';
    	$parts = $expression->getParts();
    	
    	foreach ( $parts as $part )
    	{
			/* @var $part SwsQueryPart */
			$format = '';
    		switch ( $part->getMode() )
    		{
    			case SwsQueryPhrase::MODE_AND:
				case SwsQueryPhrase::MODE_DEFAULT:
    				$format = "& ";
    				break;
				
    			case SwsQueryPhrase::MODE_OR :
    				$format = "| ";
    				break;    			
    		}
			if ($part->getNegate()) {
				$format .= '! ';
			}
						
			if ($part instanceof SwsQueryExpression) {
				$query .= $format . '(' . $this->trimQuery($this->processExpression($part)) . ') ';
			}
			else {
				$query .= $format . "'" . str_replace('\\', '\\\\', str_replace( "'", "''", $part) . "':*") . ' ';
			}			    		
    	}
    	
    	return $query;
    }
}
