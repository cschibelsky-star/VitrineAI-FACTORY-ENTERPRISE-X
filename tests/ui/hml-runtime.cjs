const { chromium } = require('playwright');
const assert = require('node:assert/strict');

(async () => {
  const browser = await chromium.launch();
  try {
    const context = await browser.newContext({ baseURL: 'http://127.0.0.1:18088' });
    const page = await context.newPage();
    await page.goto('/agenda');
    await page.waitForURL('**/login');
    await page.getByLabel('E-mail').fill('vitrineiapro@gmail.com');
    await page.getByLabel('Senha').fill(process.env.HML_ADMIN_PASSWORD);
    await page.getByRole('button', { name: 'Entrar', exact: true }).click();
    await page.waitForURL('**/agenda');
    const csrf = await page.locator('input[name="_token"]').first().inputValue();
    const response = await context.request.post('/cadastro/pessoas', {
      form: { _token: csrf, full_name: 'Pessoa sintética HML' }, maxRedirects: 0,
    });
    assert.equal(response.status(), 302);
    await page.reload();
    await page.getByRole('heading', { name: 'Novo compromisso', exact: true }).waitFor();
    const create = page.locator('form[action$="/agenda/compromissos"]').first();
    await create.getByLabel('Título', { exact: true }).fill('Compromisso HML');
    await create.getByLabel('Início', { exact: true }).fill('2026-10-19T09:00');
    await create.getByLabel('Fim', { exact: true }).fill('2026-10-19T10:00');
    await create.getByRole('button', { name: 'Criar compromisso', exact: true }).click();
    await page.getByRole('heading', { name: 'Compromisso HML', exact: true }).waitFor();
    await page.getByText('Editar ou reagendar', { exact: true }).click();
    const edit = page.locator('details form').first();
    await edit.getByLabel('Início', { exact: true }).fill('2026-10-19T11:00');
    await edit.getByLabel('Fim', { exact: true }).fill('2026-10-19T12:00');
    await edit.getByRole('button', { name: 'Salvar alterações', exact: true }).click();
    await page.getByText('Agendamento atualizado.', { exact: true }).waitFor();
    await page.goto('/atendimento');
    await page.getByLabel('Pessoa', { exact: true }).selectOption({ label: 'Pessoa sintética HML' });
    await page.getByLabel('Assunto', { exact: true }).fill('Atendimento HML');
    await page.getByRole('button', { name: 'Registrar atendimento', exact: true }).click();
    await page.getByLabel('Situação', { exact: true }).selectOption('in_progress');
    await page.getByRole('button', { name: 'Salvar acompanhamento', exact: true }).click();
    await page.getByLabel('Nova nota', { exact: true }).fill('Nota de homologação');
    await page.getByRole('button', { name: 'Adicionar nota', exact: true }).click();
    await page.getByText('Nota registrada.', { exact: true }).waitFor();
    await page.getByRole('button', { name: 'Encerrar atendimento', exact: true }).click();
    assert.equal(await page.getByRole('button', { name: 'Adicionar nota', exact: true }).count(), 0);
    await context.close();
    console.log('HML runtime: login, Cadastro reference, booking/reschedule, case/status/note/close passed.');
  } finally { await browser.close(); }
})().catch(error => { console.error(error.message); process.exit(1); });
