<?php
declare(strict_types=1);
/**
 * Adaptador nao destrutivo entre bootstrap MAT 0.1.1 e metodologia revisada 0.2.
 * Nao altera dados, nem muda a definicao ou as formulas da Temporis.
 */
final class PontuacaoCompat {
    public static function initialize(): void {
        $dir=__DIR__;
        if(!class_exists('RevisedMethodology', false) && is_file($dir.'/RevisedMethodology.php')) {
            require_once $dir.'/RevisedMethodology.php';
        }
        if(!function_exists('revised_all_evaluations') && is_file($dir.'/revised_repository.php')) {
            require_once $dir.'/revised_repository.php';
        }
    }

    public static function methodReady(): bool {
        return class_exists('RevisedMethodology', false)
            && defined('RevisedMethodology::ALL')
            && count(RevisedMethodology::ALL)===11;
    }

    public static function repositoryReady(): bool {
        return function_exists('revised_all_evaluations') && function_exists('revised_evaluation');
    }

    public static function schemaReady(): bool {
        return self::repositoryReady() && function_exists('revised_schema_ready')
            && revised_schema_ready();
    }

    public static function allEvaluations(): array {
        if(!self::repositoryReady() || !self::schemaReady())return [];
        return revised_all_evaluations();
    }

    public static function evaluation(int $id): ?array {
        if($id<1 || !self::repositoryReady() || !self::schemaReady())return null;
        return revised_evaluation($id);
    }

    /** Evita exibir um formulario que seria descartado pelo actions.php antigo. */
    public static function canSaveDraft(): bool {
        if(!self::methodReady() || !self::schemaReady())return false;
        $file=dirname(__DIR__).'/actions.php';
        if(!is_file($file))return false;
        $code=file_get_contents($file);
        return is_string($code) && str_contains($code, "'save_revised'");
    }

    public static function warning(): ?string {
        if(!self::methodReady()) return 'Metodologia revisada ausente: instale o componente oficial RevisedMethodology.php do MAT 0.2 antes de pontuar. Nenhum valor sera inventado.';
        if(!self::repositoryReady()) return 'Repositorio de rubricas ausente: o componente app/revised_repository.php do MAT 0.2 nao esta disponivel. A fila pode ser vista, mas as notas nao podem ser confirmadas.';
        if(!self::schemaReady()) return 'Tabela marco_avaliacoes_revisadas nao encontrada/acessivel. Verifique a migracao 0.2 antes de salvar notas.';
        if(!self::canSaveDraft()) return 'O actions.php desta instalacao nao oferece a acao save_revised. E possivel consultar propostas, mas nao salva-las como rascunho ate integrar o formulario oficial do MAT 0.2.';
        return null;
    }
}
