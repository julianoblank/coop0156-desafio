# Coop0156

Este documento descreve a implementação do desafio do sicredi, endpoint por endpoint: o que foi feito, por que foi feito daquela forma e como as validações foram garantidas.

---

## Estratégia geral

Antes de entrar em cada endpoint, duas decisões estruturais valem a pena ser explicadas porque afetam todos os métodos:

- **Form Requests dedicados** (`StoreClienteRequest` e `UpdateClienteRequest`) em vez de validar dentro do controller com `$request->validate(...)`. Isso segue a recomendação explícita do desafio ("validações com Form Request") e mantém o controller enxuto — a responsabilidade de validar dados de entrada não é do controller, é da camada de request.
- **Route Model Binding** (`Cliente $cliente` como parâmetro do método, em vez de `$id`). O Laravel resolve o `Cliente` automaticamente a partir do parâmetro de rota e, se não existir, lança `ModelNotFoundException` antes mesmo do método do controller ser executado. Como o `bootstrap/app.php` já força `shouldRenderJsonWhen` para rotas `api/*`, essa exceção vira automaticamente um JSON 404 — sem precisar de `findOrFail` manual em cada método.
- **Mensagens de validação em português.** Por padrão o Laravel vem com `APP_LOCALE=en`, então as mensagens de erro do `unique`, `digits`, `required` etc. saem em inglês (`"The cpf field must be 11 digits."`). Como o domínio e a API são em pt-BR, alterei `APP_LOCALE` para `pt_BR` no `.env`/`.env.example` e criei `lang/pt_BR/validation.php` com as traduções (incluindo os nomes amigáveis dos atributos, como "e-mail" e "renda mensal"). Assim o 422 retorna, por exemplo, `"O campo cpf já está em uso."` em vez do texto em inglês.

---
CRUD de Clientes
## `GET /api/clientes` — `index`

```php
public function index(): JsonResponse
{
    return response()->json(Cliente::paginate(15));
}
```

**Por quê:** o desafio pede "lista paginada de clientes". `paginate(15)` já resolve isso sem precisar computar `offset`/`limit` manualmente, e o Eloquent monta a resposta com `data`, links de paginação (`next_page_url`, `prev_page_url` etc.) e metadados (`current_page`, `total`, `per_page`). Não há filtros ou parâmetros exigidos pelo enunciado, então não adicionei nada além do necessário (sem ordenação customizada, sem parâmetro de `per_page` configurável).

---

## `POST /api/clientes` — `store`

```php
public function store(StoreClienteRequest $request): JsonResponse
{
    $cliente = Cliente::create($request->validated());

    return response()->json($cliente, 201);
}
```

**Validação (`StoreClienteRequest`):**

```php
'nome' => ['required', 'string', 'max:255'],
'cpf' => ['required', 'digits:11', 'unique:clientes,cpf'],
'email' => ['required', 'email', 'max:255', 'unique:clientes,email'],
'telefone' => ['nullable', 'string', 'max:20'],
'renda_mensal' => ['required', 'numeric', 'gt:0'],
```

**Como cada regra do enunciado foi garantida:**

- `cpf`: "exatamente 11 dígitos numéricos" → `digits:11` (não `max:11`/`min:11`, que aceitariam menos dígitos com padding ou strings não numéricas — `digits` exige exatamente 11 caracteres, todos dígitos). "único" → `unique:clientes,cpf`, que gera 422 com mensagem de erro em português (ex.: "O campo cpf já está em uso.") se já existir.
- `email`: "formato válido" → regra `email` nativa do Laravel. "único" → `unique:clientes,email`.
- `telefone`: "opcional" → `nullable`, sem `required`.
- `renda_mensal`: "numérico positivo" → `numeric` (aceita decimais, já que a coluna é `decimal(15,2)`) combinado com `gt:0` (estritamente maior que zero — `min:0` deixaria passar renda igual a zero, o que não é "positivo").

Se a validação falhar, o Laravel já retorna 422 com o corpo `{"message": ..., "errors": {...}}` automaticamente — não precisei tratar isso manualmente no controller.

O `store` retorna `201 Created` com o cliente recém-criado no corpo, seguindo a convenção REST para criação de recursos.

---

## `GET /api/clientes/{cliente}` — `show`

```php
public function show(Cliente $cliente): JsonResponse
{
    return response()->json($cliente);
}
```

**Por quê:** com o route model binding, se o `id` na URL não corresponder a nenhum cliente, o Laravel já responde 404 antes de o método ser chamado — não há necessidade de `Cliente::find($id)` + checagem manual de `null`. Isso elimina duplicação da lógica de "404 se não encontrado" em `show`, `update` e `destroy`.

---

## `PUT /api/clientes/{cliente}` — `update`

```php
public function update(UpdateClienteRequest $request, Cliente $cliente): JsonResponse
{
    $cliente->update($request->validated());

    return response()->json($cliente);
}
```

**Validação (`UpdateClienteRequest`):** as mesmas regras do `store`, com duas diferenças importantes:

```php
$cliente = $this->route('cliente');

'nome' => ['sometimes', 'required', 'string', 'max:255'],
'cpf' => ['sometimes', 'required', 'digits:11', Rule::unique('clientes', 'cpf')->ignore($cliente)],
'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('clientes', 'email')->ignore($cliente)],
'telefone' => ['nullable', 'string', 'max:20'],
'renda_mensal' => ['sometimes', 'required', 'numeric', 'gt:0'],
```

**Por quê `sometimes`:** a atualização é **parcial** — o cliente da API pode enviar só o campo que quer alterar (ex.: só `nome`), sem precisar reenviar o registro inteiro. `sometimes` faz a regra só ser aplicada quando o campo está presente no payload; se estiver ausente, é simplesmente ignorado (o valor atual no banco não é tocado). Quando o campo *é* enviado, o `required` dentro do array ainda garante que ele não venha vazio (`""`/`null`), e as demais regras (`digits:11`, `email`, `gt:0` etc.) continuam valendo normalmente.

**Por quê o `ignore()`:** sem ele, atualizar um cliente reenviando o próprio CPF/e-mail falharia a validação de unicidade contra si mesmo. `Rule::unique(...)->ignore($cliente)` exclui o próprio registro da checagem, mas ainda bloqueia CPF/e-mail pertencentes a **outro** cliente. O `$cliente` já vem resolvido pelo route model binding no momento em que o `FormRequest` monta as regras (a substituição de bindings acontece antes da validação), então não precisei buscar o modelo de novo dentro do `rules()`.

Como `$request->validated()` só traz as chaves efetivamente enviadas, `$cliente->update($request->validated())` atualiza apenas os campos presentes no payload — os demais atributos do model permanecem inalterados, tanto no banco quanto na resposta JSON.

---

## `DELETE /api/clientes/{cliente}` — `destroy`

```php
public function destroy(Cliente $cliente): JsonResponse
{
    $cliente->delete();

    return response()->json(null, 204);
}
```

**Por quê:** 404 automático via route model binding se o cliente não existir (mesmo raciocínio do `show`). Em caso de sucesso, retorna `204 No Content` com corpo vazio, exatamente como pedido no enunciado.

---

## Testes (`tests/Feature/ClienteTest.php`)

O arquivo cobre exatamente os 10 cenários pedidos no enunciado, um teste para cada:

1. Criação de cliente com dados válidos (201).
2. Falha de validação ao criar cliente sem campos obrigatórios (422).
3. Falha ao criar cliente com CPF duplicado (422).
4. Falha ao criar cliente com e-mail duplicado (422).
5. Listagem paginada de clientes (200).
6. Exibição de cliente existente por ID (200).
7. Retorno 404 ao buscar cliente inexistente.
8. Atualização parcial de cliente existente (200) — envia só `nome` e confirma que CPF/e-mail permanecem os mesmos no banco.
9. Remoção de cliente existente (204 sem body).
10. Retorno 404 ao tentar remover cliente inexistente.

Para viabilizar os testes, foi criada `database/factories/ClienteFactory.php` (não existia no scaffold) e adicionado o trait `HasFactory` ao model `Cliente`.

---

Análise de Crédito
## Decisões estruturais desta etapa

- **Lógica de negócio em um Service (`app/Services/AnaliseCreditoService.php`), não no controller.** É um critério de avaliação explícito do desafio. O `AnaliseCreditoController` ficou reduzido a: validar via Form Request, resolver a análise (404), checar o status (422) e delegar tudo o mais para o service. Toda a regra de crédito, a chamada ao Bureau e o tratamento de falhas moram no service.
- **Form Request dedicado (`StoreAnaliseCreditoRequest`)**, seguindo a mesma decisão já tomada no CRUD de clientes — validação não é responsabilidade do controller.

- **Cadastro automático do cliente.** O enunciado pede para criar o cliente automaticamente só com `nome`, `cpf` e `renda_mensal` — o formulário de análise (`analise.blade.php`) nunca coleta e-mail. Só que a tabela `clientes` (migration original do scaffold) tem `email` como `string` obrigatório e `unique`. Para não alterar o schema nem inventar um campo de e-mail que o enunciado não pede, optei por gerar um e-mail-placeholder determinístico a partir do CPF (`cliente-{cpf}@auto.coop0156.com`) apenas quando o cliente é criado por esse fluxo. Como o CPF já é único na tabela, o placeholder nunca colide. Isso resolve a constraint do banco sem tocar na migration nem no `ClienteController`/`StoreClienteRequest`, que continuam exigindo e-mail real de verdade quando o cliente é cadastrado pela própria API de clientes.

---

## `POST /api/analise-credito` — `solicitar`

```php
public function solicitar(StoreAnaliseCreditoRequest $request): JsonResponse
{
    $analise = $this->service->solicitar($request->validated());

    return response()->json($analise, 201);
}
```

**Validação (`StoreAnaliseCreditoRequest`):**

```php
'nome' => ['required', 'string', 'max:255'],
'cpf' => ['required', 'digits:11'],
'renda_mensal' => ['required', 'numeric', 'gt:0'],
'tipo_credito' => ['required', Rule::enum(TipoCredito::class)],
'valor_solicitado' => ['required', 'numeric', 'gt:0'],
```

`Rule::enum(TipoCredito::class)` garante que só `pessoal`, `imobiliario` ou `automotivo` passam, sem precisar duplicar essa lista como `in:...` — se um novo tipo de crédito for adicionado ao enum no futuro, a validação já acompanha.

**Fluxo dentro do `AnaliseCreditoService::solicitar`:**

1. **Localizar ou cadastrar o cliente** com `Cliente::firstOrCreate(['cpf' => $cpf], [...])`. Buscar por CPF é o identificador natural do domínio, e `firstOrCreate` evita duplicar cliente em solicitações repetidas para o mesmo CPF.
2. **Persistir a análise com `status = pendente`** antes de consultar o Bureau — assim, mesmo que a chamada HTTP falhe, já existe um registro rastreável da tentativa (não se perde a solicitação).
3. **Consultar o Bureau** com `Http::timeout(config('services.score_bureau.timeout'))->get("{$baseUrl}/{$cpf}")`, onde `$baseUrl = config('services.score_bureau.url')`. O scaffold original tinha esse config apontando por padrão para `/api/mock/score`, que não é a rota que existe (`/api/mock/bureau/{cpf}`) — corrigi o default em `config/services.php` e no `.env.example` para `/api/mock/bureau`. Montar a URL a partir da config (em vez de fixá-la com `url("/api/mock/bureau/{$cpf}")`) também deixa a base configurável via `.env` sem tocar em código — o que é útil para o workaround de duas portas do `php artisan serve` no Windows, descrito mais abaixo.
4. **Tratar as falhas do Bureau num único lugar** (`consultarScore`), retornando `null` para qualquer uma das três formas de falha:
   - `ConnectionException` — cobre o timeout do dígito `5` (delay de 5s): como o timeout configurado é 3s (`SCORE_BUREAU_TIMEOUT`), a chamada estoura antes da resposta chegar.
   - `$response->failed()` — cobre o HTTP 500 do dígito `4`.
   - `$response->json('score')` ausente/não numérico — cobre o JSON malformado do dígito `6`.

   Quando `consultarScore` retorna `null`, a análise é atualizada para `reprovado` com o motivo "Não foi possível consultar o Bureau de Crédito no momento." e a resposta HTTP continua sendo `201` com um corpo limpo — nunca um 500 ou uma exception estourando para o cliente da API.
5. **Aplicar as regras de crédito, nesta ordem** (a ordem importa porque a primeira reprovação encontrada já encerra a avaliação):
   1. `renda_mensal < 1500` → reprovado, sem nem olhar o score.
   2. `score < 400` → reprovado.
   3. `score` entre 400 e 699 → taxa de 4,5% a.m.; `score >= 700` → taxa de 2,9% a.m.
   4. Calculada a parcela (juros simples sobre o valor solicitado, dividido em 12 parcelas fixas), se ela ultrapassar 30% da renda mensal, a análise é **revertida para reprovado** mesmo já tendo passado nas faixas de score — por isso essa checagem é sempre a última.

O endpoint sempre responde `201 Created`, aprovado ou reprovado: a criação do recurso `AnaliseCredito` foi bem-sucedida em ambos os casos, o resultado do negócio (aprovado/reprovado) é um dado dentro do corpo, não um código de erro HTTP. Só a validação de entrada (Form Request) gera `422`.

---

## `POST /api/analise-credito/{id}/contratar` — `contratar`

```php
public function contratar(int $id): JsonResponse
{
    $analise = AnaliseCredito::findOrFail($id);

    if ($analise->status !== StatusAnalise::APROVADO) {
        return response()->json([
            'message' => 'A análise de crédito não está aprovada para contratação.',
        ], 422);
    }

    $analise = $this->service->contratar($analise);

    return response()->json($analise);
}
```

**Por quê `findOrFail` em vez de route model binding:** a rota (`routes/api.php`, que não modifiquei) declara o parâmetro como `{id}`. Renomear para `{analise}` só para habilitar o binding implícito não trazia benefício nenhum e um `findOrFail` manual já resulta em 404 JSON automático (o `bootstrap/app.php` já força `shouldRenderJsonWhen` para `api/*`).

**Diferencial de filas implementado:** em vez de atualizar direto para `contratado`, o `AnaliseCreditoService::contratar` atualiza o status para `processando_contratacao` e despacha `ProcessarContratacaoJob::dispatch($analise->id)`. Passo o `id` (não a instância inteira) para manter o payload da fila pequeno e sempre buscar o estado mais atual do banco quando o job rodar. Depois do dispatch, chamo `$analise->refresh()` antes de devolver a resposta — com `QUEUE_CONNECTION=sync` (o padrão em `phpunit.xml` e útil para rodar localmente sem worker) o job roda na hora, então sem o `refresh()` a resposta ficaria presa em `processando_contratacao` mesmo já tendo virado `contratado` no banco.

### `ProcessarContratacaoJob`

```php
public function handle(): void
{
    $analise = AnaliseCredito::find($this->analiseId);

    if (! $analise) {
        Log::warning("ProcessarContratacaoJob: análise #{$this->analiseId} não encontrada.");
        return;
    }

    $analise->update(['status' => StatusAnalise::CONTRATADO]);

    Log::info("Contratação da análise #{$this->analiseId} finalizada com sucesso.");
}
```

O guard de "análise não encontrada" é defensivo para o cenário de fila assíncrona real (`QUEUE_CONNECTION=database` + `queue:work`): entre o dispatch e a execução do job, o registro poderia teoricamente ter sido removido; o job loga um aviso e não quebra em vez de lançar `ModelNotFoundException` sem handler num worker em background.

---

## ⚠️ Testando `solicitar` manualmente com `php artisan serve` (Windows)

Ao testar o `POST /api/analise-credito` pelo Postman usando só `php artisan serve`, a requisição pode **travar por ~3 segundos e voltar reprovada por falha no Bureau**, mesmo com um CPF que deveria aprovar. Isso não é um bug da regra de negócio — é uma característica do servidor embutido do PHP no Windows:

- O `php artisan serve` no Windows é **single-threaded**: atende uma conexão por vez.
- Ao processar a requisição do Postman, o `AnaliseCreditoService` faz uma **segunda chamada HTTP para o próprio servidor** (`GET /api/mock/bureau/{cpf}`), simulando a integração com o Bureau externo.
- Como o processo já está ocupado com a primeira requisição, ele não consegue atender a segunda — a chamada trava até estourar o `SCORE_BUREAU_TIMEOUT` (3s por padrão), e a análise cai no fluxo de "falha do Bureau".
- Em Linux/macOS isso normalmente não acontece porque o `php artisan serve` consegue subir múltiplos workers via `PHP_CLI_SERVER_WORKERS` (usa `pcntl`, extensão que builds Windows do PHP não trazem).

Em produção isso nunca ocorreria (o Bureau real estaria em outro servidor); é só um artefato de testar a integração contra um mock que mora na própria aplicação.

**Como contornar sem instalar outro servidor:** suba duas instâncias do `php artisan serve` em portas diferentes — uma para o Postman chamar, outra dedicada só a responder a chamada interna ao Bureau, para elas nunca disputarem a mesma conexão.

```bash
# Terminal 1 — a API que o Postman vai chamar
php artisan serve --port=8000

# Terminal 2 — só para responder a chamada interna ao mock do Bureau
php artisan serve --port=8001
```

E aponte a URL do Bureau para a segunda instância no `.env`:

```env
SCORE_BUREAU_API_URL=http://127.0.0.1:8001/api/mock/bureau
```

Depois de editar o `.env`, reinicie o **Terminal 1** para ele carregar o novo valor (o Terminal 2 pode continuar rodando — ele só existe para atender a chamada de loopback). O Postman continua batendo em `http://localhost:8000/...` normalmente.

Isso só é necessário para testes manuais via Postman no Windows. Os testes automatizados (`php artisan test`) não sofrem com isso, pois usam `Http::fake()` e nunca fazem uma chamada de rede de verdade.

### Cuidado extra se estiver debugando com Xdebug

Se você usa Xdebug (ex.: `.vscode/launch.json` com "Listen for Xdebug"), as duas instâncias acima compartilham o **mesmo `php.ini`** — logo, as duas tentam abrir uma sessão de debug a cada requisição (`xdebug.start_with_request=yes`). Se você parar num breakpoint na requisição da porta 8000 (a do Postman), o VSCode fica ocupado com aquela sessão pausada; a chamada interna para a porta 8001 então tenta abrir a *sua própria* sessão de debug e fica esperando o VSCode ficar livre — travando até estourar o timeout, mesmo com as duas portas configuradas.

## Testes (`tests/Feature/AnaliseCreditoTest.php`)

Cobre os 8 cenários pedidos no enunciado, um teste para cada:

1. Aprovação com score alto (`850`) e taxa de 2,9%.
2. Aprovação com score médio (`550`) e taxa de 4,5%.
3. Reprovação por renda mensal insuficiente.
4. Reprovação por score muito baixo (`150`).
5. Reprovação por comprometimento de renda (parcela > 30% da renda).
6. Falha da API do Bureau (HTTP 500) — confirma resposta `201` limpa, sem crash, e `score` nulo salvo no banco.
7. Confirmação de contratação (`contratar`) de uma análise aprovada, com `Queue::fake()` + `Queue::assertPushed` confirmando que `ProcessarContratacaoJob` foi despachado com o `id` correto.
8. Criação automática do cliente ao solicitar análise com CPF novo.

Todos usam `Http::fake(['*/api/mock/bureau/*' => Http::response(...)])` para simular o Bureau sem chamada de rede real (inclusive evitando o `sleep(5)` do cenário de timeout, que nunca é exercitado de fato — o que é testado é o app respeitando o `timeout()` configurado, não a espera real de 5 segundos).

Para viabilizar os testes, foi criada `database/factories/AnaliseCreditoFactory.php` e adicionado o trait `HasFactory` ao model `AnaliseCredito`.

---

## Mensagens de erro em português

Além da implementação dos endpoints, foi feita uma mudança de configuração para adequar as mensagens de validação ao idioma do domínio:

- **`APP_LOCALE` alterado de `en` para `pt_BR`** em `.env` e `.env.example` (mantendo `APP_FALLBACK_LOCALE=en` como fallback).
- **Criado `lang/pt_BR/validation.php`** com a tradução completa das mensagens padrão de validação do Laravel (`required`, `unique`, `digits`, `email`, `gt`, etc.), incluindo nomes amigáveis para os atributos do domínio (`cpf` → CPF, `email` → e-mail, `renda_mensal` → renda mensal) e uma mensagem customizada para `cpf.digits`.

Sem essa mudança, o Laravel usa suas mensagens padrão em inglês (ex.: `"The cpf field must be 11 digits."`). Com a alteração, o mesmo erro retorna como `"O CPF deve conter exatamente 11 dígitos numéricos."` — mais consistente com o restante da API e do enunciado, que são em pt-BR.
