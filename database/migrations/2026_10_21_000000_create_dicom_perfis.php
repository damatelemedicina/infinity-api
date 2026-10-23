<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDicomPerfis extends Migration
{
    /**
     * Perfis de leitura DICOM por fabricante, editáveis pela tela de
     * administração em vez de código. Só se aplicam a equipamentos SEM leitura
     * dedicada no ExameController (CRJ, Konica, Imex e E-COM seguem em código,
     * e são testados primeiro) — ver App\Services\Dicom\DicomProfileResolver.
     */
    public function up()
    {
        Schema::create('dicom_perfis', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            // Critérios de detecção: todos os preenchidos precisam casar (contém,
            // sem diferenciar maiúsculas/acentos). Pelo menos um é obrigatório.
            $table->string('fabricante_contem')->nullable();   // Manufacturer 0008,0070
            $table->string('modelo_contem')->nullable();       // ManufacturerModelName 0008,1090
            $table->string('estacao_contem')->nullable();      // StationName 0008,1010
            $table->integer('prioridade')->default(0);         // maior primeiro
            $table->boolean('ativo')->default(true);
            // Lista ordenada de nomes de tag (ver DicomProfileResolver::TAGS): a
            // primeira com valor útil define o tipo do exame.
            $table->text('tipo_exame_tags');
            $table->boolean('usa_lateralidade')->default(false); // acrescenta ESQ/DIR (ImageLaterality)
            $table->boolean('oit_por_texto')->default(true);
            $table->string('medico_tag')->nullable();
            $table->string('empresa_tag')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();
        });

        // Tradução de valores de tag (hoje: BodyPartExamined) pro nome usado no
        // sistema. Complementa a tabela fixa do código; a do banco vence.
        Schema::create('dicom_traducoes', function (Blueprint $table) {
            $table->id();
            $table->string('origem')->unique();   // ex: HAND
            $table->string('destino');            // ex: MAO
            $table->timestamps();
        });

        // Equipamentos que já enviaram DICOM e como foram lidos — pra achar
        // aparelho novo (perfil_id nulo / tipo vazio) sem olhar log.
        Schema::create('dicom_equipamentos_vistos', function (Blueprint $table) {
            $table->id();
            $table->string('fabricante')->default('');
            $table->string('modelo')->default('');
            $table->string('estacao')->default('');
            $table->unsignedBigInteger('perfil_id')->nullable();
            $table->string('perfil_origem', 20);   // codigo | banco | generico
            $table->string('tipo_exemplo')->nullable();
            $table->timestamp('ultima_vez')->nullable();
            $table->timestamps();
            // md5(fabricante|modelo|estacao|perfil_origem): chave única por hash, porque
            // 3 colunas de 255 em utf8mb4 estouram o limite de 3072 bytes do índice.
            $table->char('chave', 32)->unique();
        });

        // Permissão do menu (Perfis > conceder ao perfil que pode administrar).
        DB::table('permissaos')->insertOrIgnore([
            'recurso' => 'MenuPerfisDicom',
            'descricao' => 'Menu: Perfis DICOM',
        ]);

        // OBRIGATÓRIO: PerfilViewModel::getPermissoes() compara o número de permissões
        // com o de autorizações de cada perfil e, se diferir, repopula (era um laço sem
        // fim). Toda permissão nova precisa já nascer com a autorização dos perfis que
        // existem — só dos que já têm alguma: perfil sem nenhuma é preenchido sob
        // demanda, e uma linha solta o deixaria "meio preenchido". Perfil 1 (admin) edita;
        // os demais começam com o menu oculto.
        $permissaoId = DB::table('permissaos')->where('recurso', 'MenuPerfisDicom')->value('id');
        $perfisPopulados = DB::table('autorizacaos')->distinct()->pluck('perfil_id');
        foreach ($perfisPopulados as $perfilId) {
            $existe = DB::table('autorizacaos')
                ->where('perfil_id', $perfilId)->where('permissao_id', $permissaoId)->exists();
            if (!$existe) {
                DB::table('autorizacaos')->insert([
                    'perfil_id' => $perfilId,
                    'permissao_id' => $permissaoId,
                    'acesso' => $perfilId == 1 ? 'EDITAR' : 'OCULTAR',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $agora = now();
        $traducoes = [
            'HAND' => 'MAO', 'ELBOW' => 'COTOVELO', 'LSPINE' => 'COLUNA LOMBAR',
            'CSPINE' => 'COLUNA CERVICAL', 'TSPINE' => 'COLUNA TORACICA', 'CHEST' => 'TORAX',
            'THORAX' => 'TORAX', 'KNEE' => 'JOELHO', 'WRIST' => 'PUNHO', 'SHOULDER' => 'OMBRO',
            'HUMERUS' => 'UMERO', 'SCAPULA' => 'ESCAPULA', 'CLAVICLE' => 'CLAVICULA',
            'HIP' => 'ART. QUADRIL', 'FOOT' => 'PE', 'ANKLE' => 'TORNOZELO', 'SKULL' => 'CRANIO',
            'ABDOMEN' => 'ABDOME', 'PELVIS' => 'PELVE',
        ];
        foreach ($traducoes as $origem => $destino) {
            DB::table('dicom_traducoes')->insert([
                'origem' => $origem, 'destino' => $destino,
                'created_at' => $agora, 'updated_at' => $agora,
            ]);
        }
    }

    public function down()
    {
        $permissaoId = DB::table('permissaos')->where('recurso', 'MenuPerfisDicom')->value('id');
        if ($permissaoId) {
            DB::table('autorizacaos')->where('permissao_id', $permissaoId)->delete();
        }
        DB::table('permissaos')->where('recurso', 'MenuPerfisDicom')->delete();
        Schema::dropIfExists('dicom_equipamentos_vistos');
        Schema::dropIfExists('dicom_traducoes');
        Schema::dropIfExists('dicom_perfis');
    }
}
