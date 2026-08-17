<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Impossibilidade;

class ImpossibilidadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Impossibilidade::truncate();
        Impossibilidade::insert([
            ['nome' => 'INTERFERENCIAS', 'empresa_id' => 1 ],
            ['nome' => 'ARTEFATOS EM GRANDE QUANTIDADE , NÃO SENDO POSSÍVEL AVALIAR A ATIVIDADE DE BASE NEM CONSTATAR PRESENÇA DE ANORMALIDADES', 'empresa_id' => 1 ],
            ['nome' => 'ARQUIVO CORROMPIDO OU INEXISTENTE', 'empresa_id' => 1 ],
            ['nome' => 'DIVERGENCIA ENTRE DADOS DO PACIENTE E EXAME ANEXADO', 'empresa_id' => 1 ],
            ['nome' => 'EXAME NÃO LAUDADO DEVIDO ERRO DE ENVIO. FAVOR ENVIAR NOVAMENTE', 'empresa_id' => 1 ],
            ['nome' => 'TRAÇADO COM DIFÍCIL INTERPRETAÇÃO POR QUALIDADE RUIM DO EXAME', 'empresa_id' => 1 ],
            ['nome' => 'EXAME NÃO LAUDADO DEVIDO ERRO DE GRAVAÇÃO : NÃO HÁ TRAÇADOS', 'empresa_id' => 1 ],
            ['nome' => 'INVERSÃO DE ELETRODOS. POR FAVOR FAÇA UM NOVO EXAME', 'empresa_id' => 1 ],
            ['nome' => 'INTERFERÊNCIA EM LINHA DE BASE. POR FAVOR FAÇA UM NOVO EXAME', 'empresa_id' => 1 ],
            ['nome' => 'NÃO HOUVE REGISTRO EM UMA OU MAIS DERIVAÇÕES', 'empresa_id' => 1 ],
            ['nome' => 'OUTRO TIPO DE EXAME ANEXADO', 'empresa_id' => 1 ],
            ['nome' => 'AUSÊNCIA DE DADOS IMPORTANTES PARA O LAUDO', 'empresa_id' => 1 ],
            ['nome' => 'TEMPO DE GRAVAÇÃO INADEQUADO, FAVOR REFAZER O EXAME', 'empresa_id' => 1 ],
            ['nome' => 'EXAME CLÍNICO, FAVOR REENVIAR COMO CLÍNICO', 'empresa_id' => 1 ],
            ['nome' => 'ENCHER MAIS OS PULMÕES E SOPRAR POR MAIS TEMPO', 'empresa_id' => 1 ],
            ['nome' => 'FAVOR REPETIR O EXAME SOPRANDO COM MAIS FORÇA DESDE O INÍCIO', 'empresa_id' => 1 ],
            ['nome' => 'SOPRAR POR MAIS TEMPO', 'empresa_id' => 1 ]
        ]);

    }
}
