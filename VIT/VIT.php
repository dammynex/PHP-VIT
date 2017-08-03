<?php

    /**
    ** @Project: VIT PHP Template Engine
    ** @Version: 0.9
    ** @Developer: Dammy
    ** @Function: Minimal PHP Template engine
    **/

    namespace VIT;

    use VIT\Exception\{ Build as BuildException, Config as ConfigException };
    
    class VIT
    {
        
        /**
        * vit's version
        */
        const VERSION = '0.9';
        
        /**
        * Allowed variable regex
        *
        * @var string
        */
        protected $_allowed_vars = '([\s]?+)([a-zA-Z0-9_]+)([\s]?+)';
        
        /**
        * Vit variables binder
        *
        * @var array
        */
        protected $_binder = array();
        
        /**
        * Default binder
        *
        * @var array
        */
        protected $_default_binder = array('{{', '}}');
        
        /**
        * Templates directory
        *
        * @var string
        */
        protected $_dir;
        
        /**
        * Vit file extension
        *
        * @var string
        */
        protected $_file_ext = 'vit';
        
        /**
        * Strings considered false
        *
        * @var array
        */
        protected $_false_statements = array('false', '0', 'null', '', 'undefined');
        
        /**
        * Includes directory
        *
        * @var string
        */
        protected $_include_dir;
        
        /**
        * Assigned vit variables
        *
        * @var array
        */
        private $_vars = array();
        
        /**
        * Constructor
        * @param {array} $config Vit configuration
        **/
        public function __construct(array $config) {
            
            $this->_dir = $config['dir'] ?? null;
            $this->_binder = $config['binder'] ?? $this->_default_binder;
            $this->_include_dir = "{$this->_dir}/includes/";
            
            #Check if the specified directory exists
            if(!$this->_dir)
                throw new ConfigException('vit build directory not specified');
            
            #If directory doesn't exist, let's try to create it
            if(!file_exists($this->_dir) && !mkdir($this->_dir))
                throw new ConfigException('specified dir: <b>'. $this->_dir. '</b> does not exist');
            
            #Check if the binder is valid
            if(!is_array($this->_binder))
                throw new ConfigException('invalid binder');

            #Check if the includes folder exists, try create it
            if(!file_exists($this->_include_dir) && !mkdir($this->_include_dir))
                throw new ConfigException
                ('unable to create the includes directory, create it manually in "'.$this->_include_dir.'"');
        }
        
        /**
        * Assign new variable to vit
        * @param {string|array} $name Variable name|Associative array of variables
        * @param {string|null} $value Variable values|null
        **/
        public function assign($name, $value = '') {
            
            if(is_array($name) && $this->isAssocArray($name)) {

                array_walk($name, function ($val, $var) use ($name) {
                    $this->setVar($var, $this->objectToArray($val));
                });
            
            } elseif($name) {
                
                $this->setVar($name, $this->objectToArray($value));
            
            } else {
                
                throw new BuildException('Invalid data assigned. read the docs');
            }
            
            return $this;
        }
        
        /**
        * Build & Interprete vit from file
        * @param {string} $filename File to build
        * @param {bool} $exec Execute result or return result
        **/
        public function build($filename, $exec = true) {
            
            $fileToBuild = $this->getFileLink($filename);

            if(!file_exists($fileToBuild)) {
                throw new BuildException
                    ("<b>{$filename}.{$this->_file_ext}</b> not found in <b>{$this->_dir}</b>");
            }

            $fileContents = file_get_contents($fileToBuild);
            $compiled = $this->buildModule($fileContents);
            
            if($exec) echo $compiled;
            return $compiled;
        }

        /**
        * Build & Interprete vit from string
        * @param {string} $fileData String to build
        **/
        public function buildFromStr($str) {
            
            return $this->buildModule($str);
        }

        /**
        * Interpret vit components, Parse vit modules
        * @param {string} $fileData Vit file data
        **/
        public function buildModule($fileData) {
            
            $parseAssign = $this->parseAssign($fileData);
            
            $parseComments = $this->parseComments($parseAssign);
            
            $parseIncludes = $this->parseIncludes($parseComments);
            
            $parseEach = $this->parseEach($parseIncludes);
            
            $parseConditions = $this->parseConditions($parseEach);
            
            $bind = $this->bind($parseConditions);
            
            $parseFilters = $this->parseFilters($bind);
            
            $parseArrays = $this->parseArrays($parseFilters);
            
            $parseCalculations = $this->parseCalculations($parseArrays);
            
            $parseOneWayConditions = $this->parseOneWayConditions($parseCalculations);
            
            $parseOneConditions = $this->parseOneConditions($parseOneWayConditions);

            $parseStringVars = $this->parseStringVars($parseOneConditions);
            
            return $parseStringVars;
        }
        
        /**
        * Bind vit variables
        * @param {string} $fileData Vit file data
        **/
        protected function bind($fileData) {
            
            $moduleRegex = $this->addBinderRegex($this->_allowed_vars);
            
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);
            
            if($hasMatch) {
                
                foreach($matches[0] as $match) {
                    $cleanVar = $this->removeSpaces($this->stripBinder($match));
                    $fileData = str_replace($match, $this->getVar($cleanVar), $fileData);
                }
            }
            
            return $fileData;
        }

        /**
        * Parse in-template assign
        * @param {string} $fileData Vit file data
        */
        protected function parseAssign($fileData) {
            
            $moduleRegex = '/\{\{#assign([\s]?+)\$([a-zA-Z0-9\_]+)([\s]?+)\-\>([\s]?+)\((.*?)\)([\s]?+)\}\}/';
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);
            
            if($hasMatch) {
                
                foreach($matches[0] as $match) {
                    
                    $rematch = preg_match($moduleRegex, $match, $rematches);
                    
                    $variableName = $rematches[2];
                    $variableValue = $this->buildFromStr($rematches[5]);
                    
                    $this->setVar($variableName, $variableValue);
                    $fileData = str_replace($match, '', $fileData);
                }
            }
            
            return $fileData;
        }
        
        /**
        * Parse vit's array statements
        * @param {string} $fileData Vit file data
        * @param {boolean} $silent Silent/Throw error for undefined values
        **/
        protected function parseArrays($fileData, $silent = false, $return = true) {

            $moduleRegex = $this->addBinderRegex('([\s]?+)([a-zA-Z0-9\[\]\s\|\_\-]+)([\s]?+)', 'i');
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);

            if($hasMatch) {

                foreach($matches[0] as $match) {

                    $rawMatch = $this->removeSpaces($this->stripBinder($match));
                    
                    $variableVars = explode('|', $rawMatch);
                    $filters = count($variableVars > 0) ? array_splice($variableVars, 1) : [];
                    $value = $this->compileFilters($this->compileArray($variableVars[0], $silent), $filters);
                    $rvalue = is_array($value) ? 'Array' : $value;
                    $fileData = str_replace($match, $rvalue, $fileData);
                }
            }

            return $fileData;
        }

        /**
        * Runs vit mini calculations
        * @param {string} $fileData Vit file data
        **/
        protected function parseCalculations($fileData) {

            $moduleRegex = $this->addBinderRegex('\#([\s]?+)\((.*?)\)([\s]?+)');
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);

            foreach($matches[0] as $match) {
                
                $rematch = preg_match($moduleRegex, $match, $rematches);
                $calculation = $this->compileConditionStatement($rematches[2]);
                $fileData = str_replace($match, $this->runFunction($calculation), $fileData);
            }
            
            return $fileData;
        }

        /**
        * Parse vit's comment statement
        **/
        protected function parseComments($fileData) {
            
            $moduleRegex = '/\{\{\!\-\-(.*?)\-\-\}\}/';
            return preg_replace($moduleRegex, '', $fileData);
        }
        
        /**
        * Parse vit's conditional statements
        * @param {string} $fileData Vit file data
        **/
        protected function parseConditions($fileData) {

            $moduleRegex = '/\{\{#if(.*?)\}\}(((?R)|.)*?)\{\{\/([\s]?+)endif([\s]?+)\}\}/is';
            $elseCheckerRegex = '/\{\{([\s]?+)else([\s]?+)\}\}/i';
            $elseIfCheckerRegex = '/\{\{([\s]?+)elseif(.*?)\}\}/i';
            
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);
            
            if($hasMatch) {

                foreach($matches[0] as $match) {
                    
                    $rematch = preg_match($moduleRegex, $match, $rematches);
                    $rawIfStatement = trim($rematches[1]);
                    
                    $ifStatement = $this->compileConditionStatement($rawIfStatement, false, true);
                    
                    $ifConditionStatement = $statement = trim($this->parseConditions($rematches[2]));
                    $hasElse = preg_match($elseCheckerRegex, $statement);
                    $newContent = $elseConditionStatement = '';
                    $hasElseIf = false;

                    if($hasElse) {
                        $conditions = preg_split($elseCheckerRegex, $statement);
                        $ifConditionStatement = $conditions[0];
                        $elseConditionStatement = $conditions[1];
                    }
                    
                    $hasElseIf = preg_match_all($elseIfCheckerRegex, $ifConditionStatement, $elseIfMatches);
                    $elseIfConditionStatements = preg_split($elseIfCheckerRegex, $ifConditionStatement);
                    
                    $conditionStatus = $this->getConditionStatus($rawIfStatement);
                    if($conditionStatus) $newContent = ($hasElseIf) ? $elseIfConditionStatements[0] : $ifConditionStatement;
                    
                    if($hasElseIf) {
                        
                        $index = 0;
                        
                        foreach($elseIfMatches[2] as $elseIfMatch) {
                            
                            if($this->isEmpty($newContent)) {
                                
                                $thisConditionStatus = $this->getConditionStatus(trim($elseIfMatch));
                                $newContent = ($thisConditionStatus) ? $elseIfConditionStatements[$index + 1] : '';
                            }
                            $index++;
                        }
                    }
                    
                    if($this->isEmpty($newContent)) $newContent = $elseConditionStatement;
                    
                    $fileData = str_replace($match, $this->parseConditions($newContent), $fileData);
                }
            }
            return $fileData;
        }

        /**
        * Parse vit's each statement
        * @param {string} $fileData Vit file data
        **/
        protected function parseEach($fileData) {

            $moduleRegex = '/\{\{#([\s]?+)each(.*?)\}\}(((?R)|.)*?)\{\{\/([\s]?+)endeach([\s]?+)\}\}/is';
            $eachElseRegex = '/\{\{([\s]?+)eachelse([\s]?+)\}\}/';
            
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);

            foreach($matches[0] as $match) {

                $rematch = preg_match($moduleRegex, $match, $rematches);
                $statement = trim($rematches[2]);
                $ifStatementContent = $statementContent = $rematches[3];
                $elseStatementContent = $newContent = '';
                $explodedStatement = explode("as", $statement);

                $mainVariableName = substr($this->removeSpaces($explodedStatement[0]), 1);
                $rawMainVariableName = $this->addBinder($mainVariableName);
                $asVariableContent = $this->removeSpaces($explodedStatement[1]);
                
                $filterAsVariable = explode(',', $asVariableContent);
                $asVariableName = $filterAsVariable[0];
                $arrNeedle = (count($filterAsVariable) == 2) ? $filterAsVariable[1] : null;
                
                $hasEachElse = preg_match($eachElseRegex, $statementContent);
                
                if($hasEachElse) {
                    
                    $statementVars = preg_split($eachElseRegex, $statementContent);
                    $ifStatementContent = $statementVars[0];
                    $elseStatementContent = $statementVars[1];
                }
                
                $mainVariableValue = $this->compileArray($mainVariableName, true) ?? $this->getVar($mainVariableName);
                
                if(is_array($mainVariableValue)) {

                    foreach($mainVariableValue as $eachVariableValue => $eachArrNeedle) {

                        if($arrNeedle) {
                            
                            $this->setVar($asVariableName, $eachVariableValue);
                            $this->setVar($arrNeedle, $eachArrNeedle);
                            
                        } else {
                            
                            $this->setVar($asVariableName, $eachArrNeedle);
                        }
                        
                        $pstatementContent = $this->parseEach($ifStatementContent);
                        
                        $piledContent = $this->parseStrings(
                            $this->parseConditions(
                                $this->parseOneWayConditions(
                                    $pstatementContent
                                )
                            )
                        );
                        
                        $newContent .= $this->parseArrays($piledContent);
                        $this->setVar($asVariableName, '');
                    }
                    
                } elseif($hasEachElse) {
                    
                    $newContent .= $this->parseEach(
                        $this->parseStrings(
                            $elseStatementContent
                            )
                        );
                }

                $fileData = str_replace($match, $newContent, $fileData);
            }

            return $fileData;
        }

        /**
        * Parse vit variable filters
        * @param {string} $fileData Vit filedata
        **/
        protected function parseFilters($fileData) : string {

            $moduleRegex = $this->addBinderRegex('([a-z0-9\|\s\\\\(\)\,\<\>\/\-\:]+)', 'i');
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);

            if($hasMatch) {

                foreach($matches[0] as $match) {

                    $filteredMatch = $this->removeSpaces($this->stripBinder($match));
                    $filteredVars = explode('|', $filteredMatch);
                    $variableName = $filteredVars[0];
                    $variableFilters = array_splice($filteredVars, 1);
                    $variableValue = $this->getVar($variableName);
                    
                    if(!$variableValue) {
                        throw new BuildException('undefined variable name <b>'. $variableName .'</b>');
                    }

                    $variableValue = $this->compileFilters($variableValue, $variableFilters);
                    $fileData = str_replace($match, $variableValue, $fileData);
                }
            }

            return $fileData;
        }
        
        /**
        * Parse file inclusions
        * @param {string} $fileData Vit file data
        **/
        protected function parseIncludes($fileData) {

            $moduleRegex = $this->addBinderRegex('#include(.*?)');
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);

            if($hasMatch) {

                foreach($matches[0] as $match) {

                    $rematch = preg_match($moduleRegex, $match, $rematches);
                    $filesToInclude = $this->removeSpaces($rematches[1]);
                    $isMultiInclude = preg_match('/\,/', $filesToInclude);
                    $concatFiledata = '';

                    if($isMultiInclude) {
                        $filesList = explode(',', $filesToInclude);
                        foreach($filesList as $fileToInclude) {

                            $rawFileName = $this->addFileExtension($fileToInclude);
                            $fileLink = $this->addFileExtension($this->_include_dir.$fileToInclude);
                            
                            if(file_exists($fileLink)) {

                            } else {
                                
                                throw new BuildException
                                    ('"<b>'.$rawFileName.'</b>" not found in '.$this->_include_dir);
                            }

                            $concatFiledata .= file_get_contents($fileLink);
                        }

                    } else {

                        $rawFileName = $this->addFileExtension($filesToInclude);
                        $fileLink = $this->addFileExtension($this->_include_dir.$filesToInclude);
                        
                        if(!file_exists($fileLink)) {
                            throw new BuildException
                                ('"<b>'.$rawFileName.'</b>" not found in '.$this->_include_dir);
                        }

                        $concatFiledata .= file_get_contents($fileLink);
                    }

                    $fileData = str_replace($match, $this->parseIncludes($concatFiledata), $fileData);
                }
            }
            return $fileData;
        }
        
        protected function parseOneWayConditions($fileData) : string {
            
            $moduleRegex = '/\{\{([\s]?+)\((.*?)\)([\s]?+)\?([\s]?+)(.*?)\:(.*?)\}\}/';
            
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);
            
            if($hasMatch) {
                
                foreach($matches[0] as $match) {
                    
                    $rematch = preg_match($moduleRegex, $fileData, $rematches);
                    
                    $rawCondition = $rematches[2];
                    $trueCondition = trim($rematches[5]);
                    $falseCondition = trim($rematches[6]);
                    
                    $conditionStatus = $this->getConditionStatus($rawCondition);
                    
                    $newContent = ($conditionStatus) ? $trueCondition : $falseCondition;
                    $fileData = str_replace($match, $newContent, $fileData);
                }
            }
            
            return $fileData;
        }
        
        /**
        * Parse inline conditions
        * @param {string} $filedata VIT file data
        *
        */
        protected function parseOneConditions($fileData) : string {
            
            $moduleRegex = '/\{\{([\s]?+)(.*?)\?\?(.*?)([\s]?+)\}\}/';
            
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);
            
            if($hasMatch) {
                
                foreach($matches[0] as $match) {
                    
                    $rematch = preg_match($moduleRegex, $fileData, $rematches);
                    
                    $mainValueContent = $rematches[2] ?? '';
                    $otherwiseValueContent = $rematches[3] ?? '';

                    $mainValue = $this->compileConditionStatement(trim($mainValueContent));
                    $otherwiseValue = $this->compileConditionStatement(trim($otherwiseValueContent));
                    
                    $newContent = ($this->isEmpty($mainValue)) ? $otherwiseValue : $mainValue;
                    $fileData = str_replace($match, $newContent, $fileData);
                }
            }
            
            return $fileData;
        }

        /**
        * Parse stand-alone string modules
        * @param {string} $fileData Vit file data to parse
        **/
        protected function parseStrings($fileData) : string {

            $bindValues = $this->bind($fileData);
            $parseFilters = $this->parseFilters($bindValues);
            $parseCalculations = $this->parseCalculations($parseFilters);
            return $parseCalculations;
        }

        protected function parseStringVars($fileData) : string {
            
            $moduleRegex = $this->addBinderRegex('([\s]?+)(\"|\')(.*?)(\"|\')(.*?)', 'i');
            
            $hasMatch = preg_match_all($moduleRegex, $fileData, $matches);
            
            if($hasMatch) {
                
                foreach($matches[0] as $match) {
                    
                    $rematch = preg_match($moduleRegex, $match, $rematches);
                    
                    $varContent = $rematches[3];
                    $varFilters = $this->removeSpaces($rematches[5]);
                    $hasVarFilters = [];
                    
                    if(!empty($varFilters)) {
                        
                        $hasVarFilters = explode('|', substr($varFilters, 1));
                    }
                    
                    $newContent = $this->compileFilters($varContent, $hasVarFilters);
                    
                    $fileData = str_replace($match, $newContent, $fileData);
                }
            }
            
            return $fileData;
        }
        
        /**
        * Adds binder to string
        * @param {string} $str String to add binder to
        **/
        private function addBinder($str) : string {
            
            return $this->_binder[0].$str.$this->_binder[1];
        }

        /**
        * Adds file extension to string
        * @param {string} $filename File name to add extension
        **/
        private function addFileExtension($filename) : string {

            return $filename.'.'.$this->_file_ext;
        }

        /**
        * Add binder in regex format to regex
        * @param {string} $regex RegExp string to add binder with
        * @param {string} $flags RegExp flags
        **/
        private function addBinderRegex($regex, $flags = '') : string {
            
            return '/'.$this->escapeRegex($this->_binder[0]).
                $regex.
                $this->escapeRegex($this->_binder[1]).'/'.$flags;
        }

        /**
        * Runs vit array compilation
        * @param {string} $arrayData Vit array string
        **/
        private function compileArray($arrayData, $silent = false) {

            $arrayObject = $this->getArrayIndexes($arrayData);
            $arrayName = $arrayObject['name'];
            $arrayIndexes = $arrayObject['indexes'];
            $variableValue = $this->getVar($arrayName);
            
            foreach($arrayIndexes as $index) {

                if(is_array($variableValue) && array_key_exists($index, $variableValue)) {

                    $variableValue = $variableValue[$index];

                } elseif($silent) {
                    
                    return 0;
                    
                } else {
                    
                    throw new BuildException
                        ('undefined array index <b>"'.$index.'"</b> for array <b>"'.$arrayName.'"</b>');
                }
            }

            return $variableValue;
        }

        /**
        * Add parsed filters
        * @param {string} $variableValue
        * @param {array} $filters
        **/
        private function compileFilters($variableValue, array $filters) {
            
            foreach($filters as $filterName) {

                $hasArgs = preg_match('/[\(\)]/', $filterName);

                if($hasArgs) {

                    $filterName = $this->removeSpaces($filterName);
                    $filterFunctionVars = explode('(', $filterName);
                    $filterFunctionName = $filterFunctionVars[0];
                    $functionArgs = str_replace(')', '', $filterFunctionVars[1]);
                    $filterFunctionArgs = array_merge([$variableValue], explode(',', $functionArgs));

                    if(!\is_callable($filterFunctionName)) {
                        throw new BuildException('undefined function <b>'.$filterFunctionName.'</b>');
                    }

                    $variableValue = call_user_func_array($filterFunctionName, $filterFunctionArgs);
                }

                if(!$hasArgs) {

                    if(!is_callable($filterName)) {
                        throw new BuildException('undefined function <b>'. $filterName .'</b>');
                    }

                    $variableValue = $filterName($variableValue);
                }
            }
            return $variableValue;
        }
        
        /**
        * Compile vit condtion statement to full bind-able sent
        * @param {string} $str String to compile
        * @param {boolen} $withQuotes Add quotes or not
        **/
        private function compileConditionStatement($str, $withQuotes = false, $silent = false) {
            
            $pregValue = "{$this->_binder[0]}$1{$this->_binder[1]}";
            $val = ($withQuotes) ? "'{$pregValue}'" : "{$pregValue}";
            
            return $this->parseStrings(
                $this->parseArrays(
                   preg_replace(
                        '/\$([a-zA-Z0-9\[\]\_]+)/i',
                        $val,
                        $str
                    ),
                    $silent
                )
            );
        }
        
        /**
        * Escape regex strings
        * @param {string} $regex RegExp chars to escape
        * @param {string} $flags RegExp flags
        **/
        private function escapeRegex($regex, $flags = '') : string {
            
            return '\\'.implode('\\', $strVars = str_split($regex));
        }
        
        /**
        * Get the array key and array index(es)
        * @param {string} $arrayData Vit array string
        **/
        private function getArrayIndexes($arrayData) : array {

            $arrayName = $this->removeSpaces(explode('[', $arrayData)[0]);
            $arrayIndexRegex = preg_match_all('/(\[(\'?)([a-zA-Z0-9\-\_]+)(\'?)\])/', $arrayData, $arrayMatches);
            $variableValue = $this->getVar($arrayName);
            
            return array('name' => $arrayName, 'indexes' => $arrayMatches[3]);
        }

        /**
        * Get condition status
        * @param {string} $statement
        * @param {string} $variableValue
        */
        private function getConditionStatus($statement) {
            
            $conditionStatus = false;
            
            if($statement) {
                if($this->hasOperator($statement)) {

                    $ifStatement = $this->compileConditionStatement($statement, true, true);
                    if($this->runFunction($ifStatement)) $conditionStatus = true;

                } else {
                    
                    $statement = $this->compileConditionStatement($statement, false, true);
                    $variableValue = $this->parseStrings($this->parseArrays($statement, true));
                    $isOtherWise = (substr($variableValue, 0, 1) === '!');

                    if($isOtherWise) $variableValue = substr($variableValue, 1);

                    $conditionStatus = !(in_array($variableValue, $this->_false_statements)) ? true : false;
                    if($isOtherWise) $conditionStatus = !$conditionStatus;

                }
            }
            
            return $conditionStatus;
        }
        
        /**
        * Get local file link
        * @param {string} $filename Vit filename without extension
        **/
        private function getFileLink(string $filename) : string {
            return "{$this->_dir}/{$filename}.{$this->_file_ext}";
        }

        /**
        * Get value of vit variable
        * @param {string} $name Variable name
        **/
        private function getVar($name) {
            return $this->_vars[$name] ?? null;
        }

        private function hasOperator($str) : bool {

            return preg_match('/([(\=\=)|(\=\=\=)|(\&\&)|(\<\=)|(\>\=)|(\>)|(\<)|(\|\|)]+)/i', $str);
        }
        
        /**
        * Check if content is empty
        */
        private function isEmpty($content) {
            
            return !(strlen(trim($content)) > 0);
        }
        
        /**
        * Convert object/array object to array
        * @param {object} $obj Object to convert
        **/
        private function objectToArray($obj) {

            if(is_object($obj)) {

                $new = (array) $obj;

            } elseif(is_array($obj)) {

                $new = [];

                foreach($obj as $key => $val) {
                    $new[$key] = $this->objectToArray($val);
                }

            } else {
                $new = $obj;
            }
            
            return $new;
        }

        /**
        * Run anonymous function
        * @param {string} $fn Function to run
        **/
        private function runFunction($fn) {
            
            $function = create_function('', 'return ('.$fn.');');
            return $function();
        }

        /**
        * Remove all spaces in string
        * @param {string} $str String to remove spaces
        */
        private function removeSpaces($str) : string {
            
            return preg_replace('/([\s]+)/', '', $str);
        }
        
        /**
        * Set vit variable
        * @param {string} $name Variable name
        * @param {string} $value Variable value
        **/
        private function setVar($name, $value) : void {
            
            $this->_vars[$name] = $value;
        }
        
        /**
        * Strip vit binder
        * @param {string} $str VIT file data
        **/
        private function stripBinder($str) : string {
            
            return str_replace($this->_binder, '', $str);
        }
        
        /**
        * Check if array is associative array
        * @param {array} $arr Array to check
        **/
        private function isAssocArray($arr) : bool {
            
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        }
        
    }
