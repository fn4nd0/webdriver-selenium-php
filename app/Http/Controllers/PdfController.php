<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class PdfController extends Controller
{
    public function convertPdfToCsv()
    {
        try {
            $pdfFilePath = env('DOWNLOAD_PATH') . DIRECTORY_SEPARATOR . 'Leitura PDF.pdf';
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfFilePath);
            $pages  = $pdf->getPages();

            $firstPageResult = '';
            $guiasResults = [];

            foreach ($pages as $pageNumber => $page) {
                $text = $page->getText();

                if ($pageNumber == 0) {
                    $firstPageResult = $this->dataFromFirstPage($text);
                } else {

                    // A página é uma guia quando tiver esse subtext
                    if (strpos($text, 'DADOS DA GUIA') !== false) {

                        $guia['first_page'] = $firstPageResult;

                        $guia_data = $this->dataFromGuia($text);
                        $guia['head'] = $guia_data['head'];
                        $guia['medical_procedures'] = $guia_data['medical_procedures'];

                        if (!empty($guiasResults)) {
                            $lastKey = count($guiasResults) - 1;

                            $lastValue = $guiasResults[$lastKey];

                            // Se for o mesmo beneficiário, só, adicionar os valores ao anterior
                            if ($lastValue['head']['16 - Nome do Beneficiário'] == $guia['head']['16 - Nome do Beneficiário']) {
                                $guiasResults[$lastKey]['head'] = $guia['head'];
                                foreach ($guia['medical_procedures'] as $procedure) {
                                    $guiasResults[$lastKey]['medical_procedures'][] = $procedure;
                                }
                            } else {
                                $guiasResults[] = $guia;
                            }

                        } else {
                            $guiasResults[] = $guia;
                        }
                    }
                }
            }

            // Abre o arquivo para escrita
            $file = fopen(public_path('demonstrativo_conta.csv'), 'w');

            // Cabeçalho do arquivo
            $first_page = array_keys($guiasResults[0]['first_page']);
            $head = array_keys($guiasResults[0]['head']);
            $head = array_merge($first_page, $head);

            foreach ($guiasResults[0]['medical_procedures'] as $procedure) {
                $head = array_merge($head, array_keys($procedure));
            }

            fputcsv($file, $head);

            // Percorre os dados
            foreach ($guiasResults as $item) {
                // Combina o head com cada medical_procedures
                foreach ($item['medical_procedures'] as $procedure) {
                    $procedureLine = [
                        trim($procedure['Data realizacao'] ?? ''),
                        trim($procedure['tabela'] ?? ''),
                        trim($procedure['codigo do procedimento'] ?? ''),
                        trim($procedure['Descricao'] ?? ''),
                        trim($procedure['grau participacao'] ?? ''),
                        trim($procedure['Valor informado'] ?? ''),
                        trim($procedure['Quant. Executada'] ?? ''),
                        trim($procedure['Valor processado'] ?? ''),
                        trim($procedure['Valor liberado'] ?? ''),
                        trim($procedure['Valor Glossa'] ?? ''),
                        trim($procedure['Código da Glosa do Procedimento'] ?? '')
                    ];

                    $line = array_merge(array_values($item['first_page']), array_values($item['head']));
                    $line = array_merge($line, array_values($procedureLine));

                    fputcsv($file, $line);
                }
            }

            // Fecha o arquivo
            fclose($file);

            // Retorna o arquivo CSV como uma resposta de download
            return response()->download(public_path('demonstrativo_conta.csv'), 'demonstrativo_conta.csv', [
                'Content-Type' => 'text/csv',
            ]);

        } catch (\Exception $e) {
            echo "<pre>";print_r($e->getMessage());echo"</pre>";
        }
    }

    public function dataFromFirstPage($text)
    {
        $pattern1 = '/1 - Registro ANS\n(\d+)/';
        $pattern3 = '/3 - Nome da Operadora\n(.*?)4 -/';
        $pattern6 = '/6 - Código na Operadora(\d+)/';
        $pattern7a = '/6 - Código na Operadora(\d+)/';
        $pattern7b = '/6 - Código na Operadora\s*\K\d+\s*(.*?)\s*7 - Nome do Contratado/';
        $pattern9 = '/9 - Número do Lote(\d+)/';
        $pattern10 = '/(?<=Nº do Protocolo \(Processo\))(\d+\/\d+\/\d{4})/';
        $pattern11 = '/10 - Nº do Protocolo \(Processo\)(\d+\/\d+\/\d{4})/';
        $pattern12 = '/12 - Código da Glosa do Protocolo(\d*)/';
        $pattern38 = '/(?<=\d\s)\d{1,3}(?:\.\d{3})*(?:,\d{2})/';
        $pattern42 = '/TOTAL DO PROTOCOLO\s+([\d.,]+)/';
        $pattern_values1 = '/(?<=38 - Valor Informado do Protocolo \(R\$\))\s*([\d.,]+\s+){2}[\d.,]+/';
        $pattern_values2 = '/(?<=Valor Informado Geral \(R\$\))\s*([\d.,]+\s+){2}[\d.,]+(?=\s+43 - Valor Processado Geral \(R\$\))/';

        preg_match($pattern1, $text, $matches1);
        preg_match($pattern3, $text, $matches3);
        preg_match($pattern6, $text, $matches6);

        preg_match($pattern7a, $text, $matches7a);
        preg_match($pattern7b, $text, $matches7b);

        preg_match($pattern9, $text, $matches9);
        preg_match($pattern10, $text, $matches10);
        preg_match($pattern11, $text, $matches11);
        preg_match($pattern12, $text, $matches12);
        preg_match($pattern38, $text, $matches38);
        preg_match($pattern_values1, $text, $matches_values_1);
        preg_match($pattern42, $text, $matches42);
        preg_match($pattern_values2, $text, $matches_values_2);

        $contratado = (isset($matches7a[1]) && isset($matches7b[1])) ? ($matches7a[1] . $matches7b[1]) : '';

        $values1 = explode(' ', $matches_values_1[0]);
        $values2 = explode(' ', $matches_values_2[0]);

        $result = [
            '1 - Registro ANS' => isset($matches1[1]) ? $matches1[1] : '',
            '3 - Nome da Operadora' => isset($matches3[1]) ? trim($matches3[1]) : '',
            '6 - Código na Operadora' => isset($matches6[1]) ? $matches6[1] : '',
            '7 - Nome do Contratado' => $contratado,
            '9 - Número do Lote' => isset($matches9[1]) ? $matches9[1] : '',
            '10 - Nº do Protocolo (Processo)' => isset($matches10[1]) ? $matches10[1] : '',
            '11- Data do Protocolo' => isset($matches11[1]) ? $matches11[1] : '',
            '12 - Código da Glosa do Protocolo' => isset($matches12[1]) ? $matches12[1] : '',
            '38 - Valor Informado do Protocolo (R$)' => isset($matches38[0]) ? $matches38[0] : '',
            '39 - Valor Processado do Protocolo (R$)' => isset($values1[1]) ? $values1[1] : '',
            '40 - Valor Liberado do Protocolo (R$) (R$)' => isset($values1[2]) ? $values1[2] : '',
            '41 - Valor Glosa do Protocolo (R$)' => isset($values1[3]) ? $values1[3] : '',
            '42 - Valor Informado Geral (R$)' => isset($matches42[1]) ? $matches42[1] : '',
            '43 - Valor Processado Geral (R$)' => isset($values2[1]) ? $values2[1] : '',
            '44 - Valor Liberado Geral (R$)' => isset($values2[2]) ? $values2[2] : '',
            '45 - Valor Glosa Geral (R$)' => isset($values2[3]) ? $values2[3] : ''
        ];

        return $result;
    }

    public function dataFromGuia($text)
    {
        $pattern13 = '/DADOS DA GUIA(\d+)\s+13 - Número da Guia no Prestador/';
        $pattern14a = '/(?<=Número da Guia no Prestador)(\d+)/';
        $pattern14b = '/13 - Número da Guia no Prestador\d+\s+(- \d+)/';
        $pattern15 = '/14 - Número da Guia Atribuido pela Operadora(\d+)\s+15 - Senha/';
        $pattern16 = '/15 - Senha([\s\S]+?)16 - Nome do Beneficiário/';
        $pattern17 = '/16 - Nome do Beneficiário([\s\S]+?)17 - Número da Carteira/';
        $pattern18 = '/17 - Número da Carteira([\s\S]+?)18 - Data Início do Faturamento/';
        $pattern19 = '/20 - Data Fim do Faturamento([\s\S]+?)19 - Hora Início do Faturamento/';
        $pattern20 = '/18 - Data Início do Faturamento([\s\S]+?) 20 - Data Fim do Faturamento/';
        $pattern22 = '/TOTAL DA GUIA\s*\r?\n\s*([\d.,]+.*)/s';

        preg_match($pattern13, $text, $matches13);
        preg_match($pattern14a, $text, $matches14a);
        preg_match($pattern14b, $text, $matches14b);
        preg_match($pattern15, $text, $matches15);
        preg_match($pattern16, $text, $matches16);
        preg_match($pattern17, $text, $matches17);
        preg_match($pattern18, $text, $matches18);
        preg_match($pattern19, $text, $matches19);
        preg_match($pattern20, $text, $matches20);
        preg_match($pattern22, $text, $matches22a);

        // 14 - Número da Guia Atribuido pela Operadora
        $numero_guia_atribuido_operadora = (isset($matches14a) && isset($matches14b)) ? $matches14a[1] . $matches14b[1] : '';

        //22 - Código da Glosa da Guia
        $total_str = isset($matches22a[1]) ? $matches22a[1] : '';
        $total_lines = explode("\n", $total_str);
        $first_line = $total_lines[0];
        preg_match('/(?<=,)\d{2}(.*)/', $first_line, $matches22b);

        // 35, 36, 37
        $needle = 'Código da Glosa34 - Valor Informado da Guia (R$)';
        $pos = strpos($text, $needle);
        $string_with_values = '';
        if ($pos !== false) {
            $string_with_values = substr($text, $pos + strlen($needle));

        }

        // Separar os valores do pé da pagina em um array
        $values = explode(" ", $string_with_values);

        $result_head = [
            '13 - Número da Guia no Prestador' => isset($matches13[1]) ? $matches13[1] : '',
            '14 - Número da Guia Atribuido pela Operadora' => $numero_guia_atribuido_operadora,
            '15 - Senha' => isset($matches15[1]) ? $matches15[1] : '',
            '16 - Nome do Beneficiário' => isset($matches16[1]) ? $matches16[1] : '',
            '17 - Número da Carteira' => isset($matches17[1]) ? $matches17[1] : '',
            '18 - Data Início do Faturamento' => isset($matches18[1]) ? $matches18[1] : '',
            '19 - Hora Início do Faturamento' => isset($matches19[1]) ? $matches19[1] : '',
            '20 - Data Fim do Faturamento' => isset($matches20[1]) ? $matches20[1] : '',
            '22 - Código da Glosa da Guia' => isset($matches22b[1]) ? $matches22b[1] : '',
            '34 - Valor Informado da Guia' => isset($values[1]) ? $values[1] : '',
            '35 - Valor Processado da Guia' => isset($values[1]) ? $values[1] : '',
            '36 - Valor Liberado da Guia' => isset($values[2]) ? $values[2] : '',
            '37 - Valor Glosa da Guia' => isset($values[3]) ? $values[3] : '',
        ];

        // Definir se existem múltiplos procedimentos ou apenas um. Cada um segue uma tratativa para extração de dados

        // encontrar a posição da frase "22 - Código da Glosa da Guia"
        $position = strpos($text, '22 - Código da Glosa da Guia');

        // extrair a data que vem depois da frase
        if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $text, $matches, 0, $position)) {
            $data = $matches[0];
        } else {
            $data = null;
        }

        $str = substr($text, $position);
        $lines = explode("\n", $str);

        // Sempre que houver mais de 10 linhas, significa que existem múltiplos procedimentos, de acordo com o padrão observado a partir do arquivo fornecido para o exercicio
        if (count($lines) > 10) {
            $procedimentos = $this->getMedicalProceduresForMultipleProcedures($text, $lines);
        }  else {
            $medical_procedures = $this->getMedicalProceduresForSingleProcedure($text, $lines);

            // Preciso retornar como array para seguir mesmo padrão de quando existem multiplos procedimentos
            $procedimentos[0] = $medical_procedures;
        }



        $result = ['head' => $result_head, 'medical_procedures' => $procedimentos];

        return $result;

    }

    public function getMedicalProceduresForMultipleProcedures($text, $lines)
    {
        // try {

           // Data de Realização
            $medical_procedures = [];
            $first_tabela_row = null;
            foreach ($lines as $lineNumber => $line) {
                $data_realizacao = null;
                $tabela = null;
                // Verificar se a linha contém uma data
                if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $line, $matche_data)) {
                    $data_realizacao = $matche_data[0];

                    if ($tabela === null) {
                        $medical_procedures[] = ['Data realizacao' => $data_realizacao];
                    }
                } else {
                    $first_tabela_row = $lineNumber - 1;
                    break;
                }
            }

            // Tabela is a code with 2 characters
            $tabela_length = 2;
            $last_tabela_row = null;
            $num_procedures = count($medical_procedures);

            // Tabela
            if ($first_tabela_row !== null) {

                for ($i = 0; $i < $num_procedures; $i++) {
                    // Save the last line with table data
                    $last_tabela_row = $first_tabela_row + $i;

                    $line = $lines[$first_tabela_row + $i];

                    // Remover a parte da data da linha
                    $line = preg_replace('/\d{2}\/\d{2}\/\d{4}/', '', $line);
                    $tabela = substr($line, 0, $tabela_length);
                    $medical_procedures[$i]['tabela'] = $tabela;
                }
            }

            $codigo_procedimento_length = null;
            $last_cod_proc_row = null;

            // Codigo do procedimento
            if ($last_tabela_row !== null) {

                for ($i = 0; $i < $num_procedures; $i++) {
                    $line = $lines[$last_tabela_row + $i];

                    $last_cod_proc_row = $last_tabela_row + $i;

                    if ($i === 0) {
                        $pattern = '/^.{2}(.*)$/';
                        $codigo_procedimento = preg_replace($pattern, '$1', $line);
                        $medical_procedures[$i]['codigo do procedimento'] = $codigo_procedimento;

                        // In the first iteracation, we get the length of the 'Código do Procedimento' field
                        $codigo_procedimento_length = strlen($codigo_procedimento);
                    } else if (($num_procedures - $i) == 1) { // last iteration
                        $pattern = '/^(\d{' . $codigo_procedimento_length  . '})/';
                        preg_match($pattern, $line, $result);
                        $codigo_procedimento = $result[1];
                        $medical_procedures[$i]['codigo do procedimento'] = $codigo_procedimento;
                    } else {
                        $medical_procedures[$i]['codigo do procedimento'] = $line;
                    }
                }
            }

            $descricao_procedimento = '';
            $valor_informado_first_row = null;

            // Descrição do Procedimento
            if ($last_cod_proc_row !== null) {
                for ($i = $last_cod_proc_row; $i < count($lines); $i++) {

                    $line = $lines[$i];

                    // First row
                    if ($i == $last_cod_proc_row) {

                        // dd($codigo_procedimento_length);
                        // dd($line);
                        if (preg_match('/\d/', $line)) {
                            $descricao_procedimento = substr($line, $codigo_procedimento_length);
                        }
                    } else {

                        // Check if the row has numbers
                        if (preg_match('/\d+,\d+/', $line) ) {
                            $valor_informado_first_row = $i;
                            break;
                        } else {
                            if (!empty($line))
                                $descricao_procedimento .= $line;
                        }
                    }
                }
            }

            foreach ($medical_procedures as $key => $value) {
                $medical_procedures[$key]['Descricao'] = $descricao_procedimento;
            }

            // Grau Participação
            foreach ($medical_procedures as $key => $value) {
                $medical_procedures[$key]['grau participacao'] = '';
            }

            // Valor Informado
            $last_valor_informado_row = null;
            if ($valor_informado_first_row !== null) {
                for ($i = 0; $i < $num_procedures; $i++) {
                    $line = $lines[$valor_informado_first_row + $i];


                    // Check if there are just decimal values
                    if (preg_match('/^\s*\d+,\d*\s*$/', $line)) {

                        $medical_procedures[$i]['Valor informado'] = $line;
                        $last_valor_informado_row = $valor_informado_first_row + $i;
                        continue;
                    } else {
                        // Found a non decimal value
                        preg_match('/\d+,\d+/', $line, $match);
                        $medical_procedures[$i]['Valor informado'] = $match[0];

                        $last_valor_informado_row = $valor_informado_first_row + $i;
                        break;
                    }
                }
            }

            $last_qtd_executada = null;

            // Quantidade Executada
            if ($last_valor_informado_row !== null) {
                for ($i = 0; $i < $num_procedures; $i++) {
                    $line = $lines[$last_valor_informado_row + $i];


                    if ($i == 0) {
                        preg_match('/\d+(?=[^\d,.]*$)/', $line, $matches);
                        $medical_procedures[$i]['Quant. Executada'] = $matches[0];
                    } else {
                        // Check if there are just non decimal values
                        if (preg_match('/^\s*\d+\s*$/', $line)) {
                            $medical_procedures[$i]['Quant. Executada'] = $line;
                        } else {

                            // This is last row
                            preg_match('/\d+\s/', $line, $matches);
                            $medical_procedures[$i]['Quant. Executada'] = $matches[0];

                            $last_qtd_executada = $last_valor_informado_row + $i;
                        }
                    }
                }
            }

            $last_valor_processado_row = null;

            // Valor Processado
            if ($last_qtd_executada !== null) {
                for ($i = 0; $i < $num_procedures; $i++) {
                    $line = $lines[$last_qtd_executada + $i];
                    if ($i == 0) {
                        if (preg_match('/^\s*\d+\s+(\d+,\d+)/', $line, $matches)) {
                            $valor_decimal = str_replace(',', '.', $matches[1]);
                            $medical_procedures[$i]['Valor processado'] = $valor_decimal;
                        }
                    } else {
                        // Last row
                        if ($i == ($num_procedures - 1)) {
                            preg_match('/\d+,\d+/', $line, $matches);
                            $medical_procedures[$i]['Valor processado'] = $matches[0];
                            $last_valor_processado_row = $last_qtd_executada + $i;
                        } else {
                            $medical_procedures[$i]['Valor processado'] = $line;
                        }
                    }
                }
            }

            $last_valor_liberado_row = null;

            // Valor Liberado
            if ($last_valor_processado_row !== null) {
                for ($i = 0; $i < $num_procedures; $i++) {
                    $line = $lines[$last_valor_processado_row + $i];
                    if ($i == 0) {
                        if (preg_match('/\d+,\d+\s+(\d+,\d+)/', $line, $matches)) {
                            $medical_procedures[$i]['Valor liberado'] = $matches[1];
                        }
                    } else {
                        // Last row
                        if ($i == ($num_procedures - 1)) {
                            preg_match('/\d+,\d+/', $line, $matches);
                            $medical_procedures[$i]['Valor liberado'] = $matches[0];
                            $last_valor_liberado_row = $last_valor_processado_row + $i;
                        } else {
                            $medical_procedures[$i]['Valor liberado'] = $line;
                        }
                    }
                }
            }

            // Valor Glosa
            if ($last_valor_liberado_row !== null) {
                for ($i = 0; $i < $num_procedures; $i++) {
                    $line = $lines[$last_valor_liberado_row + $i];
                    if ($i == 0) {
                        preg_match_all('/\d+,\d+/', $line, $matches);
                        $medical_procedures[$i]['Valor Glossa'] = $matches[0][1];
                    } else {
                        // Last row
                        if ($i == ($num_procedures - 1)) {
                            preg_match('/^\s*\d+,\d+/', $line, $matches);
                            $medical_procedures[$i]['Valor Glossa'] = $matches[0];
                        } else {
                            $medical_procedures[$i]['Valor Glossa'] = $line;
                        }
                    }
                }
            }

            // Código da Glosa do Procedimento
            $count = 0;
            $codigo_glosa_do_procedimento = [];
            foreach (array_reverse($lines) as $line) {

                $codigo_glosa_do_procedimento[] = $line;
                $count++;
                if ($count >= $num_procedures) {
                    break;
                }
            }
            $codigo_glosa_do_procedimento = array_reverse($codigo_glosa_do_procedimento);

            for ($i = 0; $i < count($medical_procedures); $i++) {
                $medical_procedures[$i]['Código da Glosa do Procedimento'] = $codigo_glosa_do_procedimento[$i];
            }

            return $medical_procedures;

        // } catch (\Throwable $e) {

        // }
    }

    private function getMedicalProceduresForSingleProcedure($text, $lines)
    {
        $medical_procedures = [];

        $dados = $lines[0] . " " . $lines[1];

        $pattern = '/Código da Glosa da Guia(\d{2}\/\d{2}\/\d{4})/';

        if (preg_match($pattern, $dados, $matches)) {
            $data_realizacao = $matches[1];
            $dados = substr($dados, strpos($dados, $data_realizacao) + strlen($data_realizacao));
        }

        $medical_procedures['Data realizacao'] = $data_realizacao;


        $tabela = substr($dados, 0, 2);
        $dados = substr($dados, 2);

        $medical_procedures['tabela'] = $tabela;

        $codigo_do_procedimento = substr($dados, 0, 8);
        $dados = substr($dados, 8);

        $medical_procedures['codigo do procedimento'] = $codigo_do_procedimento;

        $pattern = '/^(.*?)(\d+,\d+)/';
        preg_match($pattern, $dados, $matches);
        $medical_procedures['Descricao'] = $matches[1];

        preg_match('/\d+,\d+.*$/', $dados, $matches);
        $dados = $matches[0];

        // Não tem nenhum padrão de preenchimento para o campo "Grau de Participação"
        $medical_procedures['grau participacao'] = '';

        $valores = explode(' ', $dados);

        $medical_procedures['Valor informado'] = $valores[0];
        $medical_procedures['Quant. Executada'] = $valores[1];
        $medical_procedures['Valor processado'] = $valores[2];
        $medical_procedures['Valor liberado'] = $valores[3];
        $medical_procedures['Valor Glossa'] = $valores[4];

        $medical_procedures['Código da Glosa do Procedimento'] = last($lines);

        return $medical_procedures;
    }
}
