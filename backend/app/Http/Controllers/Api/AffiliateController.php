<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AffiliateWithdrawal;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    /**
     * Solicitar saque do saldo de afiliado.
     * Requer autenticação e que o usuário seja afiliado ativo.
     */
    public function requestWithdrawal(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        abort_unless($affiliate && $affiliate->active, 403, 'Você não é um afiliado ativo.');

        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $minWithdrawal = $affiliate->min_withdrawal ?? 50.0;
        abort_unless($request->amount >= $minWithdrawal, 422, "Valor mínimo para saque é R$ {$minWithdrawal}.");
        abort_unless($affiliate->balance >= $request->amount, 422, 'Saldo insuficiente para o valor solicitado.');

        // Verifica se já tem saque pendente
        abort_if(
            $affiliate->withdrawals()->where('status', 'pending')->exists(),
            422,
            'Você já possui um saque pendente aguardando aprovação.'
        );

        $withdrawal = AffiliateWithdrawal::create([
            'affiliate_id' => $affiliate->id,
            'amount' => $request->amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Solicitação de saque criada com sucesso. Aguarde a aprovação do administrador.',
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
                'created_at' => $withdrawal->created_at,
            ],
        ], 201);
    }

    /**
     * Listar saques do afiliado autenticado.
     */
    public function withdrawals(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        abort_unless($affiliate, 403, 'Você não é um afiliado.');

        $withdrawals = $affiliate->withdrawals()
            ->latest()
            ->paginate(15);

        return response()->json($withdrawals);
    }

    /**
     * Retorna o saldo e dados do afiliado autenticado.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $affiliate = $user->affiliate;

        abort_unless($affiliate && $affiliate->active, 403, 'Você não é um afiliado ativo.');

        return response()->json([
            'code' => $affiliate->code,
            'balance' => $affiliate->balance,
            'commission_rate' => $affiliate->commission_rate,
            'min_withdrawal' => $affiliate->min_withdrawal ?? 50.0,
            'cookie_duration_days' => $affiliate->cookie_duration_days ?? 30,
        ]);
    }
}
