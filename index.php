<?php 
    $f1 = "f1.php";
    $f2 = "f2.php";
    

    if(count($_GET) > 0){
        $prod = false;
    } else {
        $prod = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Comparador de arquivos
    |--------------------------------------------------------------------------
    |
    | Compara o aquivo que está sendo enviado com o arquivo de produção
    |
    */
 
    $diff  = shell_exec("diff $f1 $f2");


    /**
     * Trata o aruivo path gerado pelo sistema operacional
     * filtrando somente as linhas com diferencas, 
     * remove as linhas onde mostra o conteudo no texto modificado
     *
     * @param [type] $pathDiff
     * @return void
     */
    function tratarPathDiff($pathDiff)
    { 
       
        $pathLimpo = limparPathDif($pathDiff);

        $diferencas = preparaDiferencas($pathLimpo);
        
        return $diferencas;
    }


    /**
     * Remove o conteudo de texto das linhas do path de diferencas e 
     * retorna um array somente com as modificações (que são os cabeçalhos 
     * das linhas de alteraçoes)
     *
     * @param string $pathDiff [o path gerado pelo tortoise]
     * @return array
     */
    function limparPathDif($pathDiff)
    {
        $linhas = explode(PHP_EOL, $pathDiff);
        
        /* 
        * tira as linhas de contedo de comparacao
        */
        $linhas = array_filter($linhas, 'filtraLinhas');

        /* 
        * reindexa o array
        */
        $linhas = array_values($linhas);
        
        return $linhas;
    }


    /**
     * Resume o path dif indicado retornando em um array de 
     * texto quais foram as diferencas encontradas
     * Ex: Linha 1 do primeiro arquivo foi modificada pelas linhas de 1 a 3 do segundo arquivo
     * 
     * @param string $pathDiff
     * @return array
     */
    function resumirPathDiff($pathDiff)
    { 
        /**
         * Removo o conteudo de texto do path diff 
         */
        $pathLimpo = limparPathDif($pathDiff);

        $arrResumo = Array();

        foreach ($pathLimpo as $nroDif => $linhaDoPath) {
            $strMensagem = "";
            
            /**
             * Verifico o tipo da alteração na linha do path 
             *  se o conteudo foi adicionado deletado 
             * ou modificado. 
             */
            if(strpos($linhaDoPath, 'a') !== false){ 
                $msgMudanca = "adicionada";
                $tipoMudanca = 'a';
            } else if(strpos($linhaDoPath, 'c') !== false){ 
                $msgMudanca = "modificada";
                $tipoMudanca = 'c';
            } else if(strpos($linhaDoPath, 'd') !== false){ 
                $msgMudanca = "deletada";
                $tipoMudanca = 'd';
            }
            
            /**
             * Separo as linhas modificadas da esquerda e direita 
             * EX: o path dif indica 3c3,5
             * nesse trecho eu separo linha 3 do arquivo antigo 
             * e linhas de 3 a 5 do arquivo novo 
             */
            $linhasModificadas = explode($tipoMudanca, $linhaDoPath);


            /**
             * Defino o inicio da mensagem de resumo se será no pural
             * ou singular
             */
            if(possuiRange($linhasModificadas[0])){ 
                $aux_linhasModificadas = explode(',', $linhasModificadas[0]);
                $strMensagem = "Linhas de {$aux_linhasModificadas[0]} a {$aux_linhasModificadas[1]} 
                                    do arquivo antigo foram {$msgMudanca}s";
            } else { 
                $strMensagem = "Linha {$linhasModificadas[0]} do arquivo antigo foi {$msgMudanca}";    
            }

            /**
             * Defino o final da mensagem de resumo, se terá continuacao 
             * se será no plural ou singular
             */
            if($tipoMudanca == 'd'){ 

                //se a linha foi deletada não preciso da segunda parte da mensagem 
                $strMensagem .= "."; 

                //define fine a msg em caso de linhas adicionadas
            } else if($tipoMudanca == 'a'){ 
                
                if(possuiRange($linhasModificadas[1])){ 
                    $aux_linhasModificadas = explode(',' , $linhasModificadas[1]);
                    $strMensagem = "Linhas de {$aux_linhasModificadas[0]} a {$aux_linhasModificadas[1]} 
                                        foram {$msgMudanca}s no arquivo novo.";
                } else { 
                    $strMensagem = "Linha {$linhasModificadas[1]} foi {$msgMudanca} do arquivo novo.";    
                }

                //define a mensagem em caso de linhas moficadas 
            } else { 
                
                //Se  possui um range de linhas modifcadas Ex de range: de 4,5 (da linha 4 a 5)
                if(strpos($linhasModificadas[1], ',') !== false){ 
                    $aux_v = explode(',' , $linhasModificadas[1]);
                    $strMensagem .= " pelas linhas de {$aux_v[0]} a {$aux_v[1]} do arquivo novo.";
                } else { 
                    $strMensagem .= " pela linha {$linhasModificadas[1]} do arquivo novo.";    
                }

            }
            
            array_push($arrResumo, array('nroDif' => ++$nroDif, 'descricao' => $strMensagem));
        }
        
        return $arrResumo;
        
    }
    
    
    /**
     * Verifica se possui um range de linhas modifcadas 
     * Ex de range: de 4,5 (da linha 4 a 5)
     *
     * @param string $areaAnalisada
     * @return bool
     */
    function possuiRange($areaAnalisada)
    { 
        return strpos($areaAnalisada, ',') !== false;
    }


    /**
     * verifica se o inicio da linha é numerico.
     * utilizada como filtro para remover linhas do 
     * path de diferencas onde não representa as alterações
     *
     * @param string $linha
     * @return bool
     */
    function filtraLinhas($linha)
    { 
        if(!empty($linha) && is_numeric($linha[0])){ 
            return true;
        } else { 
            return false;
        }
    }


    /**
     * Trata a diferença entre as linhas e retorna 
     * um array de acordo com as moficacoes 
     *
     * @param [type] $linhasDiferentes
     * @return void
     */
    function preparaDiferencas($linhasDiferentes)
    { 
        $arrDiferencas = array();

        foreach ($linhasDiferentes as $linha) {
            if(strpos($linha, 'c') !== false){ 
                array_push($arrDiferencas, trataMudados($linha));
            } else if (strpos($linha, 'd') !== false){
                array_push($arrDiferencas, trataDeletados($linha));
            } else if (strpos($linha, 'a') !== false){ 
                array_push($arrDiferencas, trataAlterados($linha));
            }
        }

        return $arrDiferencas;
    }


    /**
     * Trata as linhas que foram alteradas
     *
     * @param string $linhaDiferente
     * @return array
     */
    function trataAlterados($linhaDiferente)
    { 
        $arr_linhasAlteradas = array();
        $arr_arrayV1 = array();
        $arr_arrayV2 = array();

        $valores = explode('a', $linhaDiferente);
        $val_doc1 = $valores[0];
        $val_doc2 = $valores[1];
        
        //range das linha deletada no arquivo da esquerda
        if(strpos($val_doc1, ',')){ 
            $rangeV1  = explode(',', $val_doc1);
            $primeiroRangeV1 = $rangeV1[0];
            $segundoRangeV1 = $rangeV1[1];
            
            for ($i = $primeiroRangeV1; $i <= $segundoRangeV1; $i++) { 
                array_push($arr_arrayV1, array($i, 'a'));
            }
        } else { 
            array_push($arr_arrayV1, array($val_doc1, 'a'));            
        }

        //range das linhas modificadas no arquivo da direita 
        if(strpos($val_doc2, ',')){ 
            $rangeV2  = explode(',', $val_doc2);
            $primeiroRangeV2 = $rangeV2[0];
            $segundoRangeV2 = $rangeV2[1];
            
            for ($i = $primeiroRangeV2; $i <= $segundoRangeV2; $i++) { 
                array_push($arr_arrayV2, array($i, 'a'));
            }
        } else { 
            array_push($arr_arrayV2, array($val_doc2, 'a'));            
        }
        
        //uso essa funcao para igualar o tamanho dos array de diferenca colocando 
        //   linhas vazias no mb_encode_numericentity
        addLinhasVazias($arr_arrayV1, $arr_arrayV2);
        
        //adiciono uma linha vazia 
        array_push($arr_arrayV1, array('', 'v'));
        
        array_push($arr_linhasAlteradas, $arr_arrayV1);
        array_push($arr_linhasAlteradas, $arr_arrayV2);
        
        return $arr_linhasAlteradas;    
    }


    /**
     * Trata as linhas que foram deletadas
     *
     * @param string $linhaDiferente
     * @return array
     */
    function trataDeletados($linhaDiferente)
    {
        $arr_linhasDeletadas = array();
        $arr_arrayV1 = array();
        $arr_arrayV2 = array();

        $valores = explode('d', $linhaDiferente);
        $val_doc1 = $valores[0];
        
        $val_doc2 = $valores[1];
        
        //range das linha deletada no arquivo da esquerda
        if(strpos($val_doc1, ',')){ 
            $rangeV1  = explode(',', $val_doc1);
            $primeiroRangeV1 = $rangeV1[0];
            $segundoRangeV1 = $rangeV1[1];
            
            for ($i = $primeiroRangeV1; $i <= $segundoRangeV1; $i++) { 
                array_push($arr_arrayV1, array($i, 'd'));
            }
        } else { 
            array_push($arr_arrayV1, array($val_doc1, 'd'));            
        }

        //range das linhas modificadas no arquivo da direita 
        if(strpos($val_doc2, ',')){ 
            $rangeV2  = explode(',', $val_doc2);
            $primeiroRangeV2 = $rangeV2[0];
            $segundoRangeV2 = $rangeV2[1];
            
            for ($i = $primeiroRangeV2; $i <= $segundoRangeV2; $i++) { 
                array_push($arr_arrayV2, array($i, 'd'));
            }
        } else { 
            array_push($arr_arrayV2, array($val_doc2, 'd'));            
        }

        
        //uso essa funcao para igualar o tamanho dos array de diferenca colocando 
        //   linhas vazias no mb_encode_numericentity
        addLinhasVazias($arr_arrayV1, $arr_arrayV2);
     
        //adiciono uma linha vazia 
        array_push($arr_arrayV2, array('', 'v'));

        array_push($arr_linhasDeletadas, $arr_arrayV1);
        array_push($arr_linhasDeletadas, $arr_arrayV2);
        
        return $arr_linhasDeletadas;
    }


     /**
     * Trata as linhas que foram mudadas
     *
     * @param string $linhaDiferente
     * @return array
     */
    function trataMudados($linhaDiferente)
    {
        $arr_linhasMudancas = array();
        $arr_arrayV1 = array();
        $arr_arrayV2 = array();

        $valores = explode('c', $linhaDiferente);
        $val_doc1 = $valores[0];
        
        $val_doc2 = $valores[1];
        
        //range das linhad modificadas no arquivo da esquerda
        if(strpos($val_doc1, ',')){ 
            $rangeV1  = explode(',', $val_doc1);
            $primeiroRangeV1 = $rangeV1[0];
            $segundoRangeV1 = $rangeV1[1];
            
            for ($i = $primeiroRangeV1; $i <= $segundoRangeV1; $i++) { 
                array_push($arr_arrayV1, array($i, 'c'));
            }
        } else { 
            array_push($arr_arrayV1, array($val_doc1, 'c'));            
        }

        //range das linhas modificadas no arquivo da direita 
        if(strpos($val_doc2, ',')){ 
            $rangeV2  = explode(',', $val_doc2);
            $primeiroRangeV2 = $rangeV2[0];
            $segundoRangeV2 = $rangeV2[1];
            
            for ($i = $primeiroRangeV2; $i <= $segundoRangeV2; $i++) { 
                array_push($arr_arrayV2, array($i, 'c'));
            }
        } else { 
            array_push($arr_arrayV2, array($val_doc2, 'c'));            
        }

        //uso essa funcao para igualar o tamanho dos array de diferenca colocando 
        //   linhas vazias no mb_encode_numericentity
        addLinhasVazias($arr_arrayV1, $arr_arrayV2);
        array_push($arr_linhasMudancas, $arr_arrayV1);
        array_push($arr_linhasMudancas, $arr_arrayV2);
        
        return $arr_linhasMudancas;
    }

    
    function addLinhasVazias(&$arr1, &$arr2, $sigla = 'v'){
        
        $tamanhoArray1 = count($arr1);
        $tamanhoArray2 = count($arr2);
        
        //define o maior array 
        if($tamanhoArray1 == $tamanhoArray2){ 
            return;
        } else if ($tamanhoArray1 > $tamanhoArray2){ 
            for ($i=$tamanhoArray2; $i < $tamanhoArray1 ; $i++) { 
                array_push($arr2, array('', $sigla));
            }
        }else { 
            for ( $i = $tamanhoArray1; $i < $tamanhoArray2 ; $i++) { 
                array_push($arr1, array('', $sigla));
            }
        }
    }

    function mapTemNoArray($array, $valor){
        if($array[0] == $valor){ 
            return true; 
        } 
    }
    
    // retorna a posicao do array (padrao diferenca) onde posui o valor indicado 
    function temNoArray($array, $valor){ 
      
        $arr_params = array();
        //monto o array de parametros 
        for ($i=0; $i < count($array); $i++) { 
            array_push($arr_params, $valor);
        }

        $resultArrayMapeado = array_map('mapTemNoArray', $array, $arr_params);

        $indice = array_search(true, $resultArrayMapeado);
        
        if(is_numeric($indice)){ 
            return $indice;
        } 
        
        //else if($valor == 1){ 
        //    return temNoArray($array, 0);
        //}
    }

    function montaRetorno($diff, $f1, $f2){ 
        $diffs = tratarPathDiff($diff);
        $linhas_f1 =  explode(PHP_EOL, file_get_contents($f1));
        $linhas_f2 =  explode(PHP_EOL, file_get_contents($f2));
        $retornoF1 = Array();
        $retornoF2 = Array();
        $resumo = Array();
        $diffs1 = array();
        $diffs2 = array();
        
        foreach ($diffs as $linhaDif) {
            $diffs1 = array_merge($diffs1, $linhaDif[0]);
            $diffs2 = array_merge($diffs2, $linhaDif[1]);
        }
        
        foreach ($linhas_f1 as $chave => $linha) {
            $nroLinha = ++$chave;
            $tipoDif = null;
            
            $indice = temNoArray($diffs1, $nroLinha);
            if(is_numeric($indice)){
                $tipoDif = ($diffs1[$indice][1]);
            }

            //define o tipo da linha 
            if($tipoDif === 'c'){ 
                $tipoLinha = 'modificada';
            } else if($tipoDif === 'd'){ 
                $tipoLinha = 'deletada';
            }else if($tipoDif === 'a'){ 
                $tipoLinha = 'adicionada';
            } else { 
                $tipoLinha = 'normal';
            }

            // se a primeira modificacao == 0 quer dizer 
            // que deve-se add linhas antes da primeira linha
            if($nroLinha == 1 && $diffs1[0][0] == 0){
                $proximo = 1;
                                            
                while(!empty($diffs1[$proximo]) && $diffs1[$proximo][1] == 'v'){ 
                    
                    array_push(
                        $retornoF1, array('conteudo' => '', 
                        'nro_linha' => '',
                        'tipo_linha' => 'vazio' 
                    ));
                    $proximo++;
                }
            
            }
            
            array_push(
                $retornoF1, array('conteudo' => htmlspecialchars($linha), 
                'nro_linha' => $nroLinha,
                'tipo_linha' => $tipoLinha 
            ));

       

            if($indice !== null){ 
                
                $proximo = ++$indice;

                while(!empty($diffs1[$proximo]) && $diffs1[$proximo][1] == 'v'){ 
                    
                    array_push(
                        $retornoF1, array('conteudo' => '', 
                        'nro_linha' => '',
                        'tipo_linha' => 'vazio' 
                    ));
                    $proximo++;
                }

            }
        }

        foreach ($linhas_f2 as $chave => $linha) {
            $nroLinha = ++$chave;
            $tipoDif = null;
            
            //verifica o indice da linha do arquivo no array de diferencas
            $indice = temNoArray($diffs2, $nroLinha) ;
            
            
            if($indice !== null ){
                $tipoDif = ($diffs2[$indice][1]);
            } 
                 
            //define o tipo da linha 
            if($tipoDif === 'c'){ 
                $tipoLinha = 'modificada';
            } else if($tipoDif === 'd'){ 
                $tipoLinha = 'deletada';
            }else if($tipoDif === 'a'){ 
                $tipoLinha = 'adicionada';
            } else { 
                $tipoLinha = 'normal';
            }

            // se a primeira modificacao == 0 quer dizer 
            // que deve-se add linhas antes da primeira linha
            if($nroLinha == 1 && $diffs2[0][0] == 0){
                $proximo = 1;
                                            
                while(!empty($diffs2[$proximo]) && $diffs2[$proximo][1] == 'v'){ 
                    
                    array_push(
                        $retornoF2, array('conteudo' => '', 
                        'nro_linha' => '',
                        'tipo_linha' => 'vazio' 
                    ));
                    $proximo++;
                }
            
            }

            array_push(
                $retornoF2, 
                array(
                    'conteudo' => htmlspecialchars($linha), 
                    'nro_linha' => $nroLinha,
                    'tipo_linha' => $tipoLinha 
                )
            );
            
            
            if($indice !== null){ 
                
                $proximo = ++$indice;
                
                while(!empty($diffs2[$proximo]) && $diffs2[$proximo][1] === 'v'){ 
                    
                    array_push(
                        $retornoF2, 
                        array('conteudo' => '', 
                        'nro_linha' => '',
                        'tipo_linha' => 'vazio' 
                    ));
                    
                    $proximo++;
                }

            }
           
        }
              
        $resumo = resumirPathDiff($diff);
        $retorno = Array('resumo' => $resumo, 'antigo' => $retornoF1, 'novo' =>$retornoF2);
        return $retorno;
        
    }

    function xprint($var){ 
        if(count($_GET) > 0){
            $prod = false;
        } else {
            $prod = true;
        }

        if(!$prod){ 
            var_dump($var);
        }
    }

    function eprint($var){ 
        if(count($_GET) > 0){
            $prod = false;
        } else {
            $prod = true;
        }

        if(!$prod){ 
            var_dump($var);
            exit;
        }
    }


    if($prod){
        header('Content-type: application/json');
        print json_encode(montaRetorno($diff, $f1, $f2));

    }else { 
        montaRetorno($diff, $f1, $f2);
    }

