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

## Mensagens de erro em português

Além da implementação dos endpoints, foi feita uma mudança de configuração para adequar as mensagens de validação ao idioma do domínio:

- **`APP_LOCALE` alterado de `en` para `pt_BR`** em `.env` e `.env.example` (mantendo `APP_FALLBACK_LOCALE=en` como fallback).
- **Criado `lang/pt_BR/validation.php`** com a tradução completa das mensagens padrão de validação do Laravel (`required`, `unique`, `digits`, `email`, `gt`, etc.), incluindo nomes amigáveis para os atributos do domínio (`cpf` → CPF, `email` → e-mail, `renda_mensal` → renda mensal) e uma mensagem customizada para `cpf.digits`.

Sem essa mudança, o Laravel usa suas mensagens padrão em inglês (ex.: `"The cpf field must be 11 digits."`). Com a alteração, o mesmo erro retorna como `"O CPF deve conter exatamente 11 dígitos numéricos."` — mais consistente com o restante da API e do enunciado, que são em pt-BR.
