# Teachers - Diretório de professores para Moodle

O **Teachers** (`local_teacher`) cria uma página de apresentação da equipe docente usando os usuários, papéis e cursos que já existem no Moodle. Professores aparecem automaticamente no diretório quando possuem um dos papéis configurados atribuído diretamente em um curso visível, sem cadastro paralelo, importação de dados ou tarefa de sincronização.

O visitante pode pesquisar professores, conhecer seus perfis e explorar os cursos em que atuam. A administração controla quais papéis identificam professores, quem pode acessar o diretório, a disponibilidade das mensagens e quais pessoas devem ficar fora da listagem.

**Idiomas:** [Português](#português) | [English](#english)

## Português

### O que o plugin oferece

- **Diretório automático:** lista professores a partir das atribuições de papel já existentes nos cursos, com foto, nome e até três cursos no cartão.
- **Busca e paginação:** pesquisa por nome ou sobrenome, com 18 professores por página.
- **Perfil individual:** exibe fotografia, biografia cadastrada no perfil do Moodle e todos os cursos visíveis em que o professor atua.
- **Apresentação dos cursos:** usa imagem de apresentação, nome e resumo do próprio curso, com links para as páginas nativas do Moodle.
- **Informações de currículo:** utiliza campos personalizados nativos para registrar data de formatura e endereço do LinkedIn. O tempo exibido é calculado *desde a formatura*, e não como tempo comprovado de experiência docente.
- **Contato pelo Moodle:** permite enviar uma mensagem privada diretamente do perfil, quando habilitado e autorizado pelas regras de mensageria.
- **Controle de visibilidade:** administradores podem ocultar ou restaurar professores no diretório sem remover seus papéis, cursos ou matrículas.
- **Navegação integrada:** adiciona um acesso ao diretório na navegação principal quando a funcionalidade está habilitada e as condições de acesso permitem.

A interface usa templates Mustache e estilos responsivos, e está disponível em português do Brasil e inglês.

### Quem aparece no diretório?

Para ser listado, o usuário precisa ter **um dos papéis selecionados na configuração atribuído diretamente no contexto de um curso visível**. Além disso, sua conta precisa estar confirmada, ativa e não excluída do Moodle, e o administrador não pode tê-lo ocultado manualmente do diretório.

Uma atribuição de papel apenas no contexto do sistema ou de uma categoria não basta. O plugin não cria um cadastro próprio de professores e não inclui automaticamente todo usuário que tenha a palavra "professor" no nome do papel.

Exemplo: se Maria possui o papel configurado em dois cursos visíveis, ela aparece uma única vez no diretório, e seu perfil apresenta os dois cursos. Se todos os cursos que a qualificam forem ocultados, ela deixa de aparecer.

### Instalação

Coloque o código na pasta `local/teacher` da instalação do Moodle. A partir da raiz do Moodle:

```bash
git clone https://github.com/EduardoKrausME/moodle-local_teacher.git local/teacher
php admin/cli/upgrade.php --non-interactive
```

Também é possível instalar o ZIP pela interface de administração de plugins. Nesse caso, o diretório final precisa ser `local/teacher`, e o componente identificado pelo Moodle é `local_teacher`.

Na primeira instalação, o plugin cria uma categoria de campos de perfil chamada **Currículo**, com dois campos opcionais: **Formatura** e **LinkedIn**. Eles fazem parte do sistema nativo de perfis do Moodle, não de um banco de currículos separado.

### Configuração

Acesse **Administração do site > Plugins > Plugins locais > Professores**. As opções disponíveis são:

| Configuração | Comportamento |
| --- | --- |
| Ativar diretório de professores | Habilita ou desabilita as páginas do diretório. Ativado por padrão. |
| Papéis considerados como professores | Define quais papéis atribuídos diretamente em cursos visíveis identificam os professores. Por padrão, o ID do papel selecionado é `3`; confira o papel correspondente na sua instalação. |
| Permitir acesso sem login | Permite que visitantes visualizem a listagem e os perfis. Desativado por padrão. |
| Ativar caixa de mensagens | Exibe o contato por mensagem privada para usuários autorizados. Ativado por padrão. |

Na opção administrativa **Professores excluídos da listagem**, pesquise um professor e clique em **Ocultar**. Ele desaparece imediatamente tanto da listagem quanto da página individual do diretório, mas continua com seu perfil, seus cursos e suas permissões originais no Moodle. Use **Restaurar** para voltar a exibi-lo.

### Como usar

O diretório fica disponível em:

```text
https://seu-moodle.example/local/teacher/
```

A pesquisa utiliza o campo de busca da própria página e aceita partes do nome ou sobrenome. Ao selecionar um professor, o visitante acessa uma URL individual que combina o ID do usuário com uma versão legível do seu nome, por exemplo:

```text
/local/teacher/view.php/42-maria-silva
```

O ID mantém o endereço inequívoco mesmo quando dois professores têm o mesmo nome. Endereços antigos baseados no ID ou no nome também são reconhecidos nos formatos previstos pelo plugin.

Para completar o próprio perfil, o professor pode editar sua **Descrição** e sua **Foto do usuário** no Moodle, além dos campos **Formatura** e **LinkedIn** na seção **Currículo**. Os campos de currículo só aparecem no diretório quando estão configurados como públicos; o LinkedIn deve apontar para um endereço HTTPS válido no domínio `linkedin.com`.

As imagens e os resumos dos cursos vêm das configurações dos próprios cursos. O diretório não altera as regras de inscrição ou de acesso: um visitante pode ver um curso listado e, mesmo assim, precisar se autenticar ou se matricular para acessá-lo.

### Acesso, mensagens e privacidade

O acesso de usuários autenticados exige a capacidade `local/teacher:view` no contexto do sistema. O acesso sem login depende da configuração específica para visitantes, que vem desativada por padrão. **Antes de tornar o diretório público, revise quais nomes, fotografias, biografias e cursos poderão ficar visíveis sem autenticação.**

O botão de mensagem aparece somente para usuários autenticados, quando o contato estiver habilitado e o Moodle permitir a comunicação com aquele professor. Não é possível enviar mensagens a si próprio pelo formulário. O envio usa a mensageria nativa do Moodle, exige chave de sessão válida, aceita até 2.000 caracteres em texto simples e limita envios sucessivos pela mesma sessão a um intervalo mínimo de 30 segundos.

O plugin não duplica os registros de usuários nem de cursos. Sua tabela própria, `local_teacher_excluded`, armazena apenas os IDs dos professores ocultados e a data de exclusão da listagem; esses registros são contemplados pelo provedor de privacidade do Moodle. Os dados de biografia e currículo continuam no perfil nativo do usuário.

### Se um professor não aparecer

Verifique se o diretório está habilitado, se o papel correto está selecionado, se esse papel foi atribuído **no próprio curso**, se há ao menos um curso visível e se a conta está ativa e confirmada. Confira também a lista de professores ocultados. Quando o botão de mensagem não aparece, verifique o login, a opção de contato e as permissões ou bloqueios da mensageria do Moodle.

### Licença

Distribuído sob a [GNU GPL v3 ou posterior](LICENSE). Desenvolvido por [Eduardo Kraus](https://eduardokraus.com).

---

## English

**Teachers** (`local_teacher`) provides a searchable teaching-staff directory based on Moodle's existing users, course role assignments and profiles. Teachers are discovered dynamically; there is no separate teacher registry and no scheduled synchronisation.

### Features

- **Automatic discovery:** lists users with selected roles assigned directly in visible courses, excluding deleted, suspended, unconfirmed and manually hidden users.
- **Search and pagination:** search first names, last names or full names, with 18 teachers per page.
- **Teacher cards and profiles:** display profile pictures, biographies, teaching assignments and links to visible courses.
- **Course presentation:** show course overview images and short descriptions taken from Moodle's native course settings.
- **Curriculum fields:** optional graduation date and LinkedIn profile, stored as native custom user profile fields. The displayed number of years is **time since graduation**, not verified teaching experience.
- **Private messages:** authenticated users can contact a teacher through Moodle messaging when permitted.
- **Administrative exclusions:** hide or restore teachers in the directory without changing their enrolments, roles or courses.
- **Primary navigation:** provides a directory link when enabled and accessible.

The interface uses responsive styles and Mustache templates, with English and Brazilian Portuguese translations.

### Installation

Install the repository into `local/teacher` within your Moodle installation. From the Moodle root:

```bash
git clone https://github.com/EduardoKrausME/moodle-local_teacher.git local/teacher
php admin/cli/upgrade.php --non-interactive
```

You can also upload a plugin ZIP through Moodle's plugin installer. The plugin component is `local_teacher`.

On initial installation, the plugin adds a **Curriculum** category with two optional Moodle custom profile fields: **Graduation** (date) and **LinkedIn** (text). Existing Moodle profile data remains in Moodle.

### Settings

Go to **Site administration > Plugins > Local plugins > Teachers**.

| Setting | Purpose |
| --- | --- |
| Enable teacher directory | Enables the directory pages. On by default. |
| Teacher roles | Selects roles used to identify teachers through **direct course-level assignments**. Role ID `3` is selected by default; verify which role that represents on your site. |
| Allow visitors without an account | Enables access to the listing and profiles without signing in. Off by default. |
| Enable contact form | Allows eligible signed-in users to send private messages to teachers. On by default. |

The separate **Excluded teachers** administration page lets you search for teachers, hide them from the directory and later restore them. Exclusion does not modify their Moodle roles, enrolments or user accounts.

### Finding and updating profiles

Open the directory at:

```text
https://your-moodle.example/local/teacher/
```

Selecting a teacher opens a readable, ID-based profile URL such as `/local/teacher/view.php/42-maria-silva`. The ID avoids name collisions, and supported older links continue to resolve.

Teachers can edit their photo, biography and the optional curriculum fields through their normal Moodle profile, subject to their existing permissions. LinkedIn links must use HTTPS and the `linkedin.com` domain; curriculum data is displayed only when its profile field is public. The directory displays course images and descriptions without overriding Moodle's course access or enrolment rules.

### Permissions, messaging and privacy

Signed-in visitors need the system-level `local/teacher:view` capability. Anonymous access is controlled separately and is disabled by default. If anonymous access is enabled, names, photos, biographies and information about visible courses may become accessible without authentication.

The contact form requires a signed-in user, an enabled contact setting and permission to message the teacher according to Moodle's messaging API. It uses session-key validation, plain-text messages of at most 2,000 characters and a 30-second per-session send interval.

The plugin stores no duplicate user or course profiles. Its own `local_teacher_excluded` table holds only manually excluded user IDs and exclusion timestamps, with support for Moodle's privacy export and deletion requests.

### Troubleshooting

If a teacher is missing, check the configured role IDs, **direct role assignments in visible courses**, account status and administrative exclusions. Assigning a role only at system or category level does not qualify a user. If messaging is unavailable, verify the contact setting, authentication and Moodle's messaging permissions or blocks.

### License

Licensed under the [GNU GPL v3 or later](LICENSE). Developed by [Eduardo Kraus](https://eduardokraus.com).
