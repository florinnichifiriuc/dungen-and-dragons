import { Sword, ScrollText, Map, Users } from 'lucide-react'

const quickLinks = [
  { icon: Sword, label: 'Launch Session', description: 'Jump into the live session workspace with notes, initiative, and dice.' },
  { icon: Map, label: 'Explore World Map', description: 'Open the shared hex-tile atlas and update regional progress.' },
  { icon: Users, label: 'Manage Groups', description: 'Invite parties, assign DMs to regions, and track permissions.' },
  { icon: ScrollText, label: 'Review Task Log', description: 'Check weekly milestones and implementation progress.' },
]

function App() {
  return (
    <div className="min-h-screen bg-[url('https://images.unsplash.com/photo-1523395243481-163f8f6155fa?auto=format&fit=crop&w=1600&q=80')] bg-cover bg-fixed bg-center">
      <div className="min-h-screen bg-surface/90 backdrop-blur-md">
        <header className="mx-auto flex max-w-5xl flex-col gap-6 px-6 py-16 text-center text-slate-100">
          <p className="text-sm uppercase tracking-[0.3em] text-brand/80">Dungeon &amp; Dragons Campaign Nexus</p>
          <h1 className="font-display text-4xl font-bold tracking-wide text-brand-foreground drop-shadow-md sm:text-5xl">
            A Turn-Based Multiverse for Collaborative Storytelling
          </h1>
          <p className="mx-auto max-w-3xl text-base text-slate-200 sm:text-lg">
            Coordinate worlds, campaigns, and cross-party adventures. Automate time-based turns, empower dungeon masters with AI
            assistance, and keep every session logged with rich notes, modular maps, and shared lore.
          </p>
        </header>

        <main className="mx-auto grid max-w-5xl gap-6 px-6 pb-20 sm:grid-cols-2">
          {quickLinks.map(({ icon: Icon, label, description }) => (
            <article
              key={label}
              className="group flex h-full flex-col justify-between rounded-xl border border-brand/20 bg-surface/80 p-6 shadow-lg shadow-black/30 transition hover:border-brand hover:shadow-brand-glow"
            >
              <div className="flex items-center gap-3">
                <span className="flex h-12 w-12 items-center justify-center rounded-full bg-brand/20 text-brand">
                  <Icon className="h-6 w-6" aria-hidden="true" />
                </span>
                <h2 className="font-display text-xl text-brand-foreground">{label}</h2>
              </div>
              <p className="mt-4 flex-1 text-sm leading-relaxed text-slate-300">{description}</p>
              <button className="mt-6 inline-flex items-center justify-center rounded-lg border border-brand/40 bg-brand/20 px-4 py-2 text-sm font-semibold tracking-wide text-brand-foreground transition hover:bg-brand/30">
                Enter
              </button>
            </article>
          ))}
        </main>
      </div>
    </div>
  )
}

export default App
