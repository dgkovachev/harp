import { useState, useEffect, useRef, useMemo } from 'react';

const sections = [
  { id: 'students', label: 'Ученици' },
  { id: 'admins', label: 'Администратори' },
  { id: 'clubs', label: 'Клубове' },
  { id: 'faq', label: 'ЧЗВ' }
];

const faqs = [
  {
    q: 'Мога ли да се регистрирам с личен имейл?',
    a: 'Не. Платформата приема само училищни имейл адреси. Ако нямате такъв, свържете се с администратора на вашето училище.'
  },
  {
    q: 'Нямам училищен имейл, но имам код — мога ли да влезна?',
    a: 'Да. Ако имате код на училище, можете да използвате друг имейл адрес само ако администраторът изрично го е разрешил.'
  },
  {
    q: 'Не получих потвърдителен имейл.',
    a: 'Проверете папката „Спам". Ако и там го няма, натиснете „Изпрати отново" на страницата за вход.'
  },
  {
    q: 'Виждам обяви от друго училище.',
    a: 'Това не трябва да се случва. Излезте от акаунта и влезте отново. Ако проблемът продължава, свържете се с нас.'
  }
];

function NoteParticles() {
  const configs = useMemo(() => [
    { char: '♪', left: '4%',  size: '2rem',   duration: '16s', delay: '0s' },
    { char: '♫', left: '12%', size: '2.6rem', duration: '22s', delay: '2s' },
    { char: '♩', left: '22%', size: '1.6rem', duration: '14s', delay: '5s' },
    { char: '♬', left: '32%', size: '2.2rem', duration: '20s', delay: '1s' },
    { char: '♫', left: '42%', size: '1.8rem', duration: '18s', delay: '7s' },
    { char: '♪', left: '50%', size: '2.4rem', duration: '24s', delay: '3s' },
    { char: '♩', left: '58%', size: '1.5rem', duration: '15s', delay: '9s' },
    { char: '♬', left: '66%', size: '2rem',   duration: '19s', delay: '4s' },
    { char: '♫', left: '75%', size: '2.8rem', duration: '26s', delay: '0s' },
    { char: '♪', left: '83%', size: '1.7rem', duration: '17s', delay: '6s' },
    { char: '♩', left: '91%', size: '2.1rem', duration: '21s', delay: '8s' },
    { char: '♬', left: '97%', size: '1.4rem', duration: '13s', delay: '2s' },
    { char: '♫', left: '8%',  size: '3rem',   duration: '28s', delay: '10s' },
    { char: '♪', left: '28%', size: '2.3rem', duration: '23s', delay: '4s' },
    { char: '♩', left: '48%', size: '1.9rem', duration: '16s', delay: '1s' },
    { char: '♬', left: '62%', size: '2.5rem', duration: '25s', delay: '6s' },
    { char: '♫', left: '72%', size: '1.6rem', duration: '14s', delay: '3s' },
    { char: '♪', left: '88%', size: '2.2rem', duration: '20s', delay: '9s' },
    { char: '♩', left: '16%', size: '2.7rem', duration: '27s', delay: '0s' },
    { char: '♬', left: '38%', size: '1.3rem', duration: '12s', delay: '7s' },
  ], []);

  return (
    <>
      {configs.map((c, i) => (
        <span
          key={i}
          className="note-particle"
          aria-hidden="true"
          style={{
            left: c.left,
            bottom: '-3rem',
            fontSize: c.size,
            animationDuration: c.duration,
            animationDelay: c.delay,
          }}
        >
          {c.char}
        </span>
      ))}
    </>
  );
}

export default function HelpPage({ onBack, theme, onToggleTheme }) {
  const [openFaq, setOpenFaq] = useState(null);
  const [activeSection, setActiveSection] = useState('');
  const cardRef = useRef(null);
  const sectionRefs = useRef({});

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            setActiveSection(entry.target.id);
          }
        }
      },
      { root: cardRef.current, rootMargin: '-80px 0px -60% 0px', threshold: 0 }
    );

    const refs = sectionRefs.current;
    for (const id of Object.keys(refs)) {
      if (refs[id]) observer.observe(refs[id]);
    }

    return () => observer.disconnect();
  }, []);

  const scrollTo = (id) => {
    const el = sectionRefs.current[id];
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  return (
    <main className="auth-page">
      <NoteParticles />
      <button
        type="button"
        className="theme-toggle"
        onClick={onToggleTheme}
        aria-label={theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
      >
        {theme === 'light' ? '☾' : '☀'}
      </button>
      <div className="auth-glow auth-glow-left" aria-hidden="true" />
      <div className="auth-glow auth-glow-right" aria-hidden="true" />

      <section className="help-shell" aria-labelledby="help-title">
        <div className="brand-mark" aria-hidden="true">
          <span>♫</span>
        </div>

        <header className="brand-copy">
          <h1 id="help-title">Помощ</h1>
          <p>Как да използвате платформата — Ръководство за потребители</p>
        </header>

        <nav className="help-toc" aria-label="Съдържание">
          {sections.map((s) => (
            <button
              key={s.id}
              type="button"
              className={`help-toc-item${activeSection === s.id ? ' active' : ''}`}
              onClick={() => scrollTo(s.id)}
            >
              {s.label}
            </button>
          ))}
        </nav>

        <section className="help-card" ref={cardRef}>
          <div className="help-section" ref={(el) => (sectionRefs.current['students'] = el)} id="students">
            <h2 className="help-section-title">За ученици</h2>

            <h3 className="help-subsection-title">Регистрация</h3>
            <ol className="help-list">
              <li>Отворете сайта и натиснете „Регистрация".</li>
              <li>Въведете вашия училищен имейл адрес. Лични имейли като Gmail, ABV или Yahoo не се приемат — трябва да използвате имейла, който училището ви е предоставило.</li>
              <li>Ако вашето училище ви е дало код за присъединяване, въведете го в полето „Код на училище". Ако имате код, не е нужно да се притеснявате за имейл домейна — кодът е достатъчен.</li>
              <li>Въведете вашето име и създайте парола.</li>
              <li>Натиснете „Създай акаунт".</li>
              <li>Проверете имейла си за потвърдително съобщение и натиснете линка в него. Без потвърждение няма да можете да виждате обяви.</li>
            </ol>

            <h3 className="help-subsection-title">Влизане в платформата</h3>
            <ol className="help-list">
              <li>Натиснете „Вход".</li>
              <li>Въведете вашия училищен имейл и парола.</li>
              <li>Ако имате код на училище, можете да го въведете — системата ще го използва с приоритет.</li>
              <li>Натиснете „Влез".</li>
            </ol>

            <h3 className="help-subsection-title">Какво виждате след влизане</h3>
            <ul className="help-list">
              <li>Виждате само обявите на вашето училище.</li>
              <li>Ако членувате в клуб, виждате и обявите на клуба.</li>
              <li>Непрочетените обяви са маркирани като „Ново".</li>
            </ul>
          </div>

          <div className="help-section" ref={(el) => (sectionRefs.current['admins'] = el)} id="admins">
            <h2 className="help-section-title">За администратори на училище</h2>

            <h3 className="help-subsection-title">Първо влизане</h3>
            <ol className="help-list">
              <li>Влезте с официалния имейл на училището (например info-200236@edu.mon.bg).</li>
              <li>При първо влизане системата автоматично генерира уникален код за вашето училище.</li>
              <li>Този код можете да намерите в „Настройки на училището".</li>
              <li>Споделете кода с учениците и учителите — това е най-лесният начин те да се присъединят към вашето училище в платформата.</li>
            </ol>

            <h3 className="help-subsection-title">Публикуване на обяви</h3>
            <ol className="help-list">
              <li>От началната страница натиснете „Нова обява".</li>
              <li>Въведете заглавие, текст и категория (общо, академично, събитие).</li>
              <li>По желание задайте дата на изтичане — обявата автоматично се скрива след нея.</li>
              <li>Натиснете „Публикувай".</li>
              <li>Обявата веднага се вижда от всички ученици и учители от вашето училище.</li>
            </ol>
          </div>

          <div className="help-section" ref={(el) => (sectionRefs.current['clubs'] = el)} id="clubs">
            <h2 className="help-section-title">За ръководители на клубове</h2>

            <h3 className="help-subsection-title">Създаване на клуб</h3>
            <ol className="help-list">
              <li>Влезте в акаунта си.</li>
              <li>Отидете в „Клубове" и натиснете „Създай клуб".</li>
              <li>Въведете името на клуба и изчакайте одобрение от администратора на училището.</li>
              <li>След одобрение можете да публикувате обяви, видими само за членовете на клуба.</li>
            </ol>

            <h3 className="help-subsection-title">Добавяне на членове</h3>
            <ul className="help-list">
              <li>Споделете името на клуба с учениците.</li>
              <li>Те го намират в „Клубове" и натискат „Присъедини се".</li>
            </ul>
          </div>

          <div className="help-section" ref={(el) => (sectionRefs.current['faq'] = el)} id="faq">
            <h2 className="help-section-title">Често задавани въпроси</h2>
            <div className="help-faq-list">
              {faqs.map((item, index) => (
                <div key={index} className={`help-faq-item${openFaq === index ? ' open' : ''}`}>
                  <button
                    type="button"
                    className="help-faq-question"
                    onClick={() => setOpenFaq(openFaq === index ? null : index)}
                    aria-expanded={openFaq === index}
                  >
                    <span>{item.q}</span>
                    <span className="help-faq-chevron" aria-hidden="true">›</span>
                  </button>
                  <div className="help-faq-answer" role="region">
                    <p>{item.a}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          <button type="button" className="primary-action" onClick={onBack}>
            ← Назад към входа
          </button>
        </section>

        <p className="connection-note">Connected to <code>harp</code></p>
      </section>
    </main>
  );
}
