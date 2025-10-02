<?php

namespace DppManualParser;

class FieldFormatSplitter implements SplitterInterface
{
    public function handle(string $content): array
    {
        $chunks = preg_split('/Campo\s+Formatação\s+/m', $content);

        array_shift($chunks);

        $fields = [];
        foreach ($chunks as $c)
        {
            if (preg_match_all('/^\s*(.+?)\s{2,}(Texto|Data\s[X\/]{10}|N\úmero.+)/m', $c, $matches))
            {
                $n = min(sizeof($matches[1]), sizeof($matches[2]));
                for ($i = 0; $i < $n; $i++) {
                    $sec['field'] = $this->mapLabel($matches[1][$i]);
                    $sec['type'] = $this->format($matches[2][$i]);
                    $sec['required'] = 'NULLABLE';
                    $sec['length'] = '';
                    $sec['filling_rule'] = $c;
                    array_push($fields, $sec);
                }
            }
        }

        $output = array_combine(array_column($fields, 'field'), $fields);
        return $output;
    }

    private function mapLabel($str)
    {
        $translation = [
            "Filial" => "COD_FILIAL",
            "Código Cadastro Poço" => "COD_CADASTRO_POCO",
            "Data Inicio Completação" => "DAT_INI_COMPLETACAO",
            "Data Término Completação" => "DAT_TERMINO_COMPLETACAO",
            "Unidade Completação" => "UNIDADE_COMPLETACAO",
            "Observações" => "OBSERVACOES",
            "Tipo Conexão" => "TIPO_CONEXAO",
            "Diâmetro (pol)" => "DIAMETRO",
            "Diâmetro/Topo (m)" => "INT_DIAMETRO_TOPO",
            "Diâmetro/Base (m)" => "INT_DIAMETRO_BASE",
            "Peso (lb/pé)" => "PESO",
            "Peso/Topo (m)" => "INT_PESO_TOPO",
            "Peso/Base (m)" => "INT_PESO_BASE",
            "º API" => "GRAU",
            "º API/Topo (m)" => "INT_GRAU_TOPO",
            "º API/Base (m)" => "INT_GRAU_BASE",
            "Unidade Estratigráfica" => "UNID_ESTRATIGRAFICA",
            "Topo (m)" => "TOPO",
            "Base (m)" => "BASE",
            "Saturação Água (%)" => "SATURACAO_AGUA",
            "Porosidade (%)" => "POROSIDADE",
            "Net Pay (m)" => "NET_PAY",
            "Tipo de Canhão" => "TIPO_CANHAO",
            "Densidade Disparos" => "DENSIDADE_DISPAROS",
            "Tipo de Ácido" => "TIPO_ACIDO",
            "Vol. de Ácido Injetado (m³)" => "VOL_INJETADO",
            "Tipo de Solvente" => "TIPO_SOLVENTE",
            "MESH" => "MESH",
            "Tipo de Contenção" => "TIPO_CONTENCAO",
            "Vol. De Gravel (m³)" => "VOL_GRAVEL",
            "Tipo Agente de Sustentação" => "TIPO_AGENTE_SUSTENTACAO",
            "Vol. Agente de Sustentação (m³)" => "VOL_AGENTE_SUSTENTACAO",
            "Equipamento" => "TXT_EQUIPAMENTO",
            "O.D. (pol)" => "OD",
            "I.D. (pol)" => "ID",
            "Tipo de Teste" => "TIPO_TESTE",
            "Abertura (pol)" => "ABERTURA",
            "Vazão (m³/dia)" => "VAZAO",
            "Pressão a Montante (psi)" => "PRESSAO_MONTANTE",
            "RGO (m³/m³)" => "RGO",
            "BSW (%)" => "BSW",
            "Areia (%)" => "AREIA",
            "TIPO" => "TIPO_HC",
            "º API" => "API",
            "DG" => "DG",
            "Prof. Vertical (m)" => "PROF_VERTICAL",
            "Pressão Estática Extrapolada (kgf/cm²)" => "PRESSAO_ESTATICA_ESTRAPOLADA",
            "Pressão de Fluxo Final (kgf/cm²)" => "PRESSAO_FLUXO_FINAL",
            "K (mD)" => "K",
            "IP (m³/dia/kgf/cm²)" => "IP",
            "Dano" => "DANO"
        ];

        return trim($translation[$str] ?? $str);
    }

    private function format($str)
    {
        $str = trim($str);
        $find = ['.'];
        $replace = array_map(function ($el) {
            return '';
        }, $find);
        $str = str_replace($find, $replace, $str);
        $str = strip_accents($str);

        if (preg_match('/NUMERO\s(\d)(?=.+)/i', $str, $matches)) {
            return "DECIMAL(10," . $matches[1] . ")";
        }

        return $str;
    }
}
