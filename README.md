# LANSE Dashboard Block (block_mad2api)

Bloco para Moodle que integra cursos à plataforma **LANSE**, permitindo habilitar/desabilitar o envio de dados, sincronizar informações e abrir um painel externo via LTI.

---

## ✨ Funcionalidades

- Exibe botões no curso:
  - **Abrir Dashboard**: abre o painel LANSE (via `view.php` + LTI).
  - **Habilitar/Desabilitar** o envio de dados do curso para a API.
  - **Carregar Dados Agora**: força a sincronização manual com a API.
- Sincronização agendada (via *Scheduled tasks*):
  - **Agências** (`block_mad2api\task\mad_agencies`)
  - **Boletos** (`block_mad2api\task\mad_bills`)
  - **Logs de curso** (`block_mad2api\task\mad_logger`)
  - **Transferências** (`block_mad2api\task\mad_transfer`)
- Observa eventos do Moodle (inscrição, conclusão de atividades, notas) e registra em `block_block_mad2api_course_logs`.
- Suporte a **LTI 1.3** para abertura do dashboard.
- Implementação da **Privacy API**, com exportação e eliminação de dados pessoais.
- Suporte a **AMD/RequireJS** para chamadas AJAX.

---

## 📦 Requisitos

- Moodle **4.1 – 4.4** (ajuste conforme sua instalação)
- PHP **>= 8.0**
- Recomenda-se rodar `cron.php` regularmente para execução das tarefas agendadas.

---

## ⚙️ Instalação

1. Baixe ou clone este repositório.
2. Copie a pasta para: `moodle/blocks/mad2api`
3. Acesse **Administração do site → Notificações** para instalar.  
4. Configure o plugin em **Administração do site → Plugins → Blocos → LANSE Dashboard**.

---

## 🔧 Configuração

- **Configurações globais**  
- `API URL`: endpoint da API LANSE/MAD.  
- `Access Key` e `Secret Key`: credenciais da integração.  
- `User Roles`: papéis do Moodle autorizados a visualizar o bloco (ex.: professor, coordenador).  

- **Configuração por curso**  
- Adicione o bloco **LANSE Dashboard** na página do curso.  
- Use os botões para habilitar ou desabilitar a integração no curso.  

---

## 🔒 Privacidade

Este plugin armazena e exporta **dados pessoais** para a plataforma **LANSE**.

- **Armazenados localmente em `block_block_mad2api_course_logs`:**
- `userid`, `courseid`, `action`, `payload`, `status`, `createdat`.

- **Enviados a serviços externos (LANSE/MAD API):**
- Identificador do usuário  
- Nome completo  
- Endereço de e-mail  
- Matrículas  
- Notas  
- Progresso em atividades  
- Último acesso  

O plugin implementa os provedores da **Moodle Privacy API** para exportação e eliminação desses dados.

---

## 🔐 Permissões

- `block/mad2api:addinstance` – adicionar o bloco a cursos.  
- `block/mad2api:myaddinstance` – adicionar ao *Painel do Usuário*.  
- `block/mad2api:view` – visualizar e usar o bloco.
