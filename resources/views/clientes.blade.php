<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes — Coop0156</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        coop: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            900: '#14532d',
                        },
                        darkBg: '#0b0f19',
                        panelBg: '#131c2e',
                        panelBorder: '#1e2d4a',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #0b0f19;
            background-image:
                radial-gradient(at 0% 0%, hsla(142, 70%, 15%, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, hsla(220, 70%, 15%, 0.15) 0px, transparent 50%);
        }
        .glass-panel {
            background: rgba(19, 28, 46, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(30, 45, 74, 0.6);
        }
    </style>
</head>
<body class="text-slate-200 min-h-screen flex flex-col font-sans">

    <!-- Header / Navbar -->
    <header class="border-b border-panelBorder/50 py-5 glass-panel sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 flex justify-between items-center">
            <a href="/" class="flex items-center gap-3 group">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-green-500 to-emerald-600 flex items-center justify-center shadow-lg shadow-green-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight bg-gradient-to-r from-emerald-400 to-green-300 bg-clip-text text-transparent">Coop0156</h1>
                    <p class="text-xs text-slate-400">Desafio Análise de Crédito</p>
                </div>
            </a>
            <a href="/" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Nova Análise
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow max-w-6xl mx-auto px-4 py-12 w-full">

        <!-- Cabeçalho da página -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-bold text-white">Clientes</h2>
                <p class="text-slate-400 mt-1">Cadastre, edite e gerencie os clientes da cooperativa.</p>
            </div>
            <button id="btn-novo-cliente"
                class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200 shadow-lg shadow-emerald-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Novo Cliente
            </button>
        </div>

        <!-- Alertas -->
        <div id="alerta-erro" class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6 text-red-400 text-sm hidden"></div>
        <div id="alerta-sucesso" class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 mb-6 text-emerald-400 text-sm hidden"></div>

        <!-- Tabela de Clientes -->
        <div class="glass-panel rounded-3xl shadow-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-panelBorder text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-4 font-medium">Nome</th>
                            <th class="px-6 py-4 font-medium whitespace-nowrap">CPF</th>
                            <th class="px-6 py-4 font-medium">E-mail</th>
                            <th class="px-6 py-4 font-medium">Telefone</th>
                            <th class="px-6 py-4 font-medium text-right">Renda Mensal</th>
                            <th class="px-6 py-4 font-medium text-center">Análises</th>
                            <th class="px-6 py-4 font-medium text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-clientes" class="divide-y divide-panelBorder">
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-slate-500">Carregando clientes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div id="paginacao" class="flex items-center justify-between px-6 py-4 border-t border-panelBorder text-sm text-slate-400"></div>
        </div>

    </main>

    <!-- Modal de Cadastro/Edição -->
    <div id="modal-cliente" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
        <div class="glass-panel rounded-3xl p-8 max-w-lg w-full shadow-2xl relative">
            <button id="btn-fechar-modal" type="button" class="absolute top-6 right-6 text-slate-500 hover:text-slate-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h3 id="modal-titulo" class="text-2xl font-semibold mb-6">Novo Cliente</h3>

            <form id="form-cliente" class="space-y-5">
                <input type="hidden" id="cliente_id" value="">

                <div>
                    <label for="cliente_nome" class="block text-sm font-medium text-slate-400 mb-2">Nome Completo</label>
                    <input type="text" id="cliente_nome" name="nome" required placeholder="Digite o nome completo"
                        class="w-full bg-slate-950/50 border border-panelBorder rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                    <p class="erro-campo text-red-400 text-xs mt-1 hidden"></p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="cliente_cpf" class="block text-sm font-medium text-slate-400 mb-2">CPF</label>
                        <input type="text" id="cliente_cpf" name="cpf" required placeholder="000.000.000-00" maxlength="14"
                            class="w-full bg-slate-950/50 border border-panelBorder rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                        <p class="erro-campo text-red-400 text-xs mt-1 hidden"></p>
                    </div>
                    <div>
                        <label for="cliente_telefone" class="block text-sm font-medium text-slate-400 mb-2">Telefone</label>
                        <input type="text" id="cliente_telefone" name="telefone" placeholder="(00) 00000-0000"
                            class="w-full bg-slate-950/50 border border-panelBorder rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                        <p class="erro-campo text-red-400 text-xs mt-1 hidden"></p>
                    </div>
                </div>

                <div>
                    <label for="cliente_email" class="block text-sm font-medium text-slate-400 mb-2">E-mail</label>
                    <input type="email" id="cliente_email" name="email" required placeholder="cliente@email.com"
                        class="w-full bg-slate-950/50 border border-panelBorder rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                    <p class="erro-campo text-red-400 text-xs mt-1 hidden"></p>
                </div>

                <div>
                    <label for="cliente_renda" class="block text-sm font-medium text-slate-400 mb-2">Renda Mensal (R$)</label>
                    <input type="number" step="0.01" id="cliente_renda" name="renda_mensal" required placeholder="Ex: 3500.00"
                        class="w-full bg-slate-950/50 border border-panelBorder rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all">
                    <p class="erro-campo text-red-400 text-xs mt-1 hidden"></p>
                </div>

                <div class="flex gap-4 pt-2">
                    <button type="button" id="btn-cancelar-modal"
                        class="flex-1 py-3.5 rounded-xl border border-panelBorder text-slate-400 hover:text-slate-200 hover:border-slate-500 transition-all font-medium text-sm">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-salvar-cliente"
                        class="flex-1 bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-semibold py-3.5 px-6 rounded-xl transition-all duration-200 flex items-center justify-center gap-2">
                        <span id="txt-salvar-cliente">Salvar</span>
                        <svg id="spinner-salvar-cliente" class="animate-spin h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Confirmação de Remoção -->
    <div id="modal-remover" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
        <div class="glass-panel rounded-3xl p-8 max-w-sm w-full shadow-2xl text-center">
            <div class="h-14 w-14 bg-red-500/10 text-red-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Remover cliente?</h3>
            <p id="texto-remover" class="text-sm text-slate-400 mb-6">Esta ação não pode ser desfeita.</p>
            <div class="flex gap-4">
                <button type="button" id="btn-cancelar-remover"
                    class="flex-1 py-3 rounded-xl border border-panelBorder text-slate-400 hover:text-slate-200 hover:border-slate-500 transition-all font-medium text-sm">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-remover"
                    class="flex-1 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white font-semibold py-3 px-6 rounded-xl transition-all duration-200">
                    Remover
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Análises do Cliente -->
    <div id="modal-analises" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
        <div class="glass-panel rounded-3xl p-8 max-w-5xl w-full shadow-2xl relative max-h-[85vh] flex flex-col">
            <button id="btn-fechar-modal-analises" type="button" class="absolute top-6 right-6 text-slate-500 hover:text-slate-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h3 id="modal-analises-titulo" class="text-2xl font-semibold mb-1">Análises de Crédito</h3>
            <p id="modal-analises-subtitulo" class="text-sm text-slate-400 mb-6"></p>

            <div class="overflow-y-auto overflow-x-auto -mx-8 px-8">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-panelBorder text-left text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-4 py-3 font-medium whitespace-nowrap">Data</th>
                            <th class="px-4 py-3 font-medium whitespace-nowrap">Tipo</th>
                            <th class="px-4 py-3 font-medium text-right whitespace-nowrap">Valor Solicitado</th>
                            <th class="px-4 py-3 font-medium text-center whitespace-nowrap">Score</th>
                            <th class="px-4 py-3 font-medium text-center whitespace-nowrap">Status</th>
                            <th class="px-4 py-3 font-medium text-right whitespace-nowrap">Parcela</th>
                        </tr>
                    </thead>
                    <tbody id="tabela-analises" class="divide-y divide-panelBorder">
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Carregando análises...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-panelBorder/40 py-6 text-center text-xs text-slate-600">
        <div class="max-w-6xl mx-auto px-4">
            <p>&copy; 2026 CoopCred. Todos os direitos reservados. Desafio Técnico Laravel.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabela = document.getElementById('tabela-clientes');
            const paginacao = document.getElementById('paginacao');
            const alertaErro = document.getElementById('alerta-erro');
            const alertaSucesso = document.getElementById('alerta-sucesso');

            const modalCliente = document.getElementById('modal-cliente');
            const modalTitulo = document.getElementById('modal-titulo');
            const formCliente = document.getElementById('form-cliente');
            const clienteIdInput = document.getElementById('cliente_id');

            const btnNovoCliente = document.getElementById('btn-novo-cliente');
            const btnFecharModal = document.getElementById('btn-fechar-modal');
            const btnCancelarModal = document.getElementById('btn-cancelar-modal');
            const btnSalvarCliente = document.getElementById('btn-salvar-cliente');
            const txtSalvarCliente = document.getElementById('txt-salvar-cliente');
            const spinnerSalvarCliente = document.getElementById('spinner-salvar-cliente');

            const modalRemover = document.getElementById('modal-remover');
            const textoRemover = document.getElementById('texto-remover');
            const btnCancelarRemover = document.getElementById('btn-cancelar-remover');
            const btnConfirmarRemover = document.getElementById('btn-confirmar-remover');

            const modalAnalises = document.getElementById('modal-analises');
            const modalAnalisesSubtitulo = document.getElementById('modal-analises-subtitulo');
            const tabelaAnalises = document.getElementById('tabela-analises');
            const btnFecharModalAnalises = document.getElementById('btn-fechar-modal-analises');

            let paginaAtual = 1;
            let clienteParaRemover = null;

            const formatarMoeda = (valor) => Number(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            const formatarCpf = (cpf) => (cpf ?? '').replace(/\D/g, '').replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');

            const mascararCpf = (valor) => valor
                .replace(/\D/g, '')
                .slice(0, 11)
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');

            const exibirErro = (mensagem) => {
                alertaErro.textContent = mensagem;
                alertaErro.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            const ocultarErro = () => alertaErro.classList.add('hidden');

            const exibirSucesso = (mensagem) => {
                alertaSucesso.textContent = mensagem;
                alertaSucesso.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                clearTimeout(exibirSucesso.timeoutId);
                exibirSucesso.timeoutId = setTimeout(ocultarSucesso, 4000);
            };

            const ocultarSucesso = () => alertaSucesso.classList.add('hidden');

            const limparErrosFormulario = () => {
                formCliente.querySelectorAll('.erro-campo').forEach((el) => {
                    el.textContent = '';
                    el.classList.add('hidden');
                });
            };

            const exibirErrosFormulario = (errors) => {
                limparErrosFormulario();
                Object.entries(errors ?? {}).forEach(([campo, mensagens]) => {
                    const input = formCliente.querySelector(`[name="${campo}"]`);
                    const container = input?.closest('div')?.querySelector('.erro-campo');
                    if (container) {
                        container.textContent = mensagens[0];
                        container.classList.remove('hidden');
                    }
                });
            };

            const abrirModalCliente = (cliente = null) => {
                formCliente.reset();
                limparErrosFormulario();
                if (cliente) {
                    modalTitulo.textContent = 'Editar Cliente';
                    clienteIdInput.value = cliente.id;
                    document.getElementById('cliente_nome').value = cliente.nome;
                    document.getElementById('cliente_cpf').value = formatarCpf(cliente.cpf);
                    document.getElementById('cliente_email').value = cliente.email;
                    document.getElementById('cliente_telefone').value = cliente.telefone ?? '';
                    document.getElementById('cliente_renda').value = cliente.renda_mensal;
                } else {
                    modalTitulo.textContent = 'Novo Cliente';
                    clienteIdInput.value = '';
                }
                modalCliente.classList.remove('hidden');
            };

            const fecharModalCliente = () => modalCliente.classList.add('hidden');

            btnNovoCliente.addEventListener('click', () => abrirModalCliente());
            btnFecharModal.addEventListener('click', fecharModalCliente);
            btnCancelarModal.addEventListener('click', fecharModalCliente);

            document.getElementById('cliente_cpf').addEventListener('input', (event) => {
                event.target.value = mascararCpf(event.target.value);
            });

            const alternarSalvando = (salvando) => {
                btnSalvarCliente.disabled = salvando;
                spinnerSalvarCliente.classList.toggle('hidden', !salvando);
                txtSalvarCliente.textContent = salvando ? 'Salvando...' : 'Salvar';
            };

            formCliente.addEventListener('submit', async (event) => {
                event.preventDefault();
                ocultarErro();
                ocultarSucesso();
                limparErrosFormulario();
                alternarSalvando(true);

                const id = clienteIdInput.value;
                const payload = {
                    nome: document.getElementById('cliente_nome').value,
                    cpf: document.getElementById('cliente_cpf').value.replace(/\D/g, ''),
                    email: document.getElementById('cliente_email').value,
                    telefone: document.getElementById('cliente_telefone').value || null,
                    renda_mensal: parseFloat(document.getElementById('cliente_renda').value),
                };

                const url = id ? `/api/clientes/${id}` : '/api/clientes';
                const method = id ? 'PUT' : 'POST';

                try {
                    const response = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        if (response.status === 422 && data.errors) {
                            exibirErrosFormulario(data.errors);
                        } else {
                            exibirErro(data.message ?? 'Não foi possível salvar o cliente.');
                        }
                        return;
                    }

                    fecharModalCliente();
                    await carregarClientes(paginaAtual);
                } catch (error) {
                    console.error(error);
                    exibirErro('Falha de comunicação com o servidor. Tente novamente.');
                } finally {
                    alternarSalvando(false);
                }
            });

            const abrirModalRemover = (cliente) => {
                clienteParaRemover = cliente;
                textoRemover.textContent = `Tem certeza que deseja remover "${cliente.nome}"? Esta ação não pode ser desfeita.`;
                modalRemover.classList.remove('hidden');
            };

            const fecharModalRemover = () => {
                modalRemover.classList.add('hidden');
                clienteParaRemover = null;
            };

            btnCancelarRemover.addEventListener('click', fecharModalRemover);

            btnConfirmarRemover.addEventListener('click', async () => {
                if (!clienteParaRemover) return;
                ocultarErro();
                ocultarSucesso();
                btnConfirmarRemover.disabled = true;

                const nomeCliente = clienteParaRemover.nome;

                try {
                    const response = await fetch(`/api/clientes/${clienteParaRemover.id}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        const data = await response.json().catch(() => ({}));
                        exibirErro(data.message ?? `Não foi possível remover o cliente "${nomeCliente}".`);
                        return;
                    }

                    fecharModalRemover();
                    await carregarClientes(paginaAtual);
                    exibirSucesso(`Cliente "${nomeCliente}" removido com sucesso.`);
                } catch (error) {
                    console.error(error);
                    exibirErro('Falha de comunicação com o servidor. Tente novamente.');
                } finally {
                    btnConfirmarRemover.disabled = false;
                }
            });

            const STATUS_LABELS = {
                pendente: { texto: 'Pendente', classe: 'bg-amber-500/10 text-amber-400 border border-amber-500/20' },
                aprovado: { texto: 'Aprovado', classe: 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' },
                reprovado: { texto: 'Reprovado', classe: 'bg-red-500/10 text-red-400 border border-red-500/20' },
                processando_contratacao: { texto: 'Processando', classe: 'bg-blue-500/10 text-blue-400 border border-blue-500/20' },
                contratado: { texto: 'Contratado', classe: 'bg-slate-500/10 text-slate-300 border border-slate-500/20' },
            };

            const formatarData = (valor) => valor
                ? new Date(valor).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                : '-';

            const renderizarLinhaAnalise = (analise) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-white/5 transition-colors';

                const status = STATUS_LABELS[analise.status] ?? { texto: analise.status, classe: 'bg-slate-500/10 text-slate-400 border border-slate-500/20' };
                const parcela = analise.valor_parcela ? `R$ ${formatarMoeda(analise.valor_parcela)}` : '-';

                tr.innerHTML = `
                    <td class="px-4 py-3 text-slate-300 whitespace-nowrap">${formatarData(analise.created_at)}</td>
                    <td class="px-4 py-3 text-slate-300 whitespace-nowrap">${analise.tipo_credito ?? '-'}</td>
                    <td class="px-4 py-3 text-right text-slate-200 whitespace-nowrap">R$ ${formatarMoeda(analise.valor_solicitado)}</td>
                    <td class="px-4 py-3 text-center text-slate-300 whitespace-nowrap">${analise.score ?? '-'}</td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${status.classe}">${status.texto}</span>
                    </td>
                    <td class="px-4 py-3 text-right text-slate-200 whitespace-nowrap">${parcela}</td>
                `;

                return tr;
            };

            const fecharModalAnalises = () => modalAnalises.classList.add('hidden');
            btnFecharModalAnalises.addEventListener('click', fecharModalAnalises);
            modalAnalises.addEventListener('click', (event) => {
                if (event.target === modalAnalises) fecharModalAnalises();
            });

            const abrirModalAnalises = async (cliente) => {
                modalAnalisesSubtitulo.textContent = `${cliente.nome} — ${formatarCpf(cliente.cpf)}`;
                tabelaAnalises.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Carregando análises...</td></tr>';
                modalAnalises.classList.remove('hidden');

                try {
                    const response = await fetch(`/api/clientes/${cliente.id}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        tabelaAnalises.innerHTML = `<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">${data.message ?? 'Não foi possível carregar as análises.'}</td></tr>`;
                        return;
                    }

                    const analises = data.analises ?? [];
                    tabelaAnalises.innerHTML = '';

                    if (analises.length === 0) {
                        tabelaAnalises.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Nenhuma análise encontrada para este cliente.</td></tr>';
                        return;
                    }

                    analises.forEach((analise) => tabelaAnalises.appendChild(renderizarLinhaAnalise(analise)));
                } catch (error) {
                    console.error(error);
                    tabelaAnalises.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-400">Falha de comunicação com o servidor. Tente novamente.</td></tr>';
                }
            };

            const renderizarLinha = (cliente) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-white/5 transition-colors';

                const possuiAnalises = (cliente.analises_count ?? 0) > 0;

                tr.innerHTML = `
                    <td class="px-6 py-4 font-medium text-slate-100">${cliente.nome}</td>
                    <td class="px-6 py-4 font-mono text-slate-300 whitespace-nowrap">${formatarCpf(cliente.cpf)}</td>
                    <td class="px-6 py-4 text-slate-300">${cliente.email}</td>
                    <td class="px-6 py-4 text-slate-300">${cliente.telefone ?? '-'}</td>
                    <td class="px-6 py-4 text-right text-slate-200">R$ ${formatarMoeda(cliente.renda_mensal)}</td>
                    <td class="px-6 py-4 text-center">
                        <button type="button" data-acao="ver-analises" title="Ver análises de crédito"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors ${possuiAnalises ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 cursor-pointer' : 'bg-slate-500/10 text-slate-400 border border-slate-500/20 cursor-default'}">
                            ${cliente.analises_count ?? 0}
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <button type="button" data-acao="editar" class="text-emerald-400 hover:text-emerald-300 font-medium text-xs mr-4 transition-colors">Editar</button>
                        <button type="button" data-acao="remover" ${possuiAnalises ? 'disabled title="Cliente possui análise de crédito vinculada e não pode ser removido"' : ''}
                            class="font-medium text-xs transition-colors ${possuiAnalises ? 'text-slate-600 cursor-not-allowed' : 'text-red-400 hover:text-red-300'}">
                            Remover
                        </button>
                    </td>
                `;

                tr.querySelector('[data-acao="editar"]').addEventListener('click', () => abrirModalCliente(cliente));

                if (!possuiAnalises) {
                    tr.querySelector('[data-acao="remover"]').addEventListener('click', () => abrirModalRemover(cliente));
                }

                if (possuiAnalises) {
                    tr.querySelector('[data-acao="ver-analises"]').addEventListener('click', () => abrirModalAnalises(cliente));
                }

                return tr;
            };

            const renderizarPaginacao = (meta) => {
                paginacao.innerHTML = '';
                if (!meta || meta.last_page <= 1) return;

                const info = document.createElement('span');
                info.textContent = `Página ${meta.current_page} de ${meta.last_page} (${meta.total} cliente${meta.total === 1 ? '' : 's'})`;
                paginacao.appendChild(info);

                const nav = document.createElement('div');
                nav.className = 'flex gap-2';

                const btnAnterior = document.createElement('button');
                btnAnterior.textContent = 'Anterior';
                btnAnterior.disabled = meta.current_page <= 1;
                btnAnterior.className = `px-3 py-1.5 rounded-lg border border-panelBorder text-xs transition-colors ${btnAnterior.disabled ? 'text-slate-600 cursor-not-allowed' : 'text-slate-300 hover:border-slate-500'}`;
                btnAnterior.addEventListener('click', () => carregarClientes(meta.current_page - 1));

                const btnProxima = document.createElement('button');
                btnProxima.textContent = 'Próxima';
                btnProxima.disabled = meta.current_page >= meta.last_page;
                btnProxima.className = `px-3 py-1.5 rounded-lg border border-panelBorder text-xs transition-colors ${btnProxima.disabled ? 'text-slate-600 cursor-not-allowed' : 'text-slate-300 hover:border-slate-500'}`;
                btnProxima.addEventListener('click', () => carregarClientes(meta.current_page + 1));

                nav.appendChild(btnAnterior);
                nav.appendChild(btnProxima);
                paginacao.appendChild(nav);
            };

            async function carregarClientes(pagina = 1) {
                ocultarErro();
                tabela.innerHTML = '<tr><td colspan="7" class="px-6 py-10 text-center text-slate-500">Carregando clientes...</td></tr>';

                try {
                    const response = await fetch(`/api/clientes?page=${pagina}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await response.json();

                    if (!response.ok) {
                        exibirErro(data.message ?? 'Não foi possível carregar os clientes.');
                        tabela.innerHTML = '';
                        return;
                    }

                    paginaAtual = data.current_page ?? 1;
                    tabela.innerHTML = '';

                    if (!data.data || data.data.length === 0) {
                        tabela.innerHTML = '<tr><td colspan="7" class="px-6 py-10 text-center text-slate-500">Nenhum cliente cadastrado ainda.</td></tr>';
                        paginacao.innerHTML = '';
                        return;
                    }

                    data.data.forEach((cliente) => tabela.appendChild(renderizarLinha(cliente)));
                    renderizarPaginacao(data);
                } catch (error) {
                    console.error(error);
                    exibirErro('Falha de comunicação com o servidor. Tente novamente.');
                    tabela.innerHTML = '';
                }
            }

            carregarClientes();
        });
    </script>
</body>
</html>
