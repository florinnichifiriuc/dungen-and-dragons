import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Map, ScrollText, Users2, Waypoints } from 'lucide-react';
import type { ComponentType, SVGProps } from 'react';

interface FeatureCard {
    title: string;
    description: string;
    href: string;
    icon: ComponentType<SVGProps<SVGSVGElement>>;
}

const features: FeatureCard[] = [
    {
        title: 'Launch Session',
        description: 'Enter the session workspace with notes, initiative, and modular tools.',
        href: '/session-workspace',
        icon: ScrollText,
    },
    {
        title: 'Explore World Map',
        description: 'Reveal the shared tile atlas and update regional progression.',
        href: '/world-map',
        icon: Map,
    },
    {
        title: 'Manage Groups',
        description: 'Invite parties, assign DMs to regions, and track permissions.',
        href: '/groups',
        icon: Users2,
    },
    {
        title: 'Review Task Log',
        description: 'Check weekly milestones, implementation progress, and changelog.',
        href: '/tasks',
        icon: Waypoints,
    },
];

const safeRoute = (name: Parameters<typeof route>[0], fallback: string) => {
    try {
        return route(name);
    } catch (error) {
        if (import.meta.env.DEV) {
            // eslint-disable-next-line no-console
            console.warn(`Missing Ziggy route '${String(name)}', falling back to '${fallback}'.`, error);
        }

        return fallback;
    }
};

export default function Welcome() {
    const heroCtaLabel = 'Create Account';
    const registerHref = safeRoute('register', '/register');
    const loginHref = safeRoute('login', '/login');

    return (
        <>
            <Head title="Campaign Nexus" />
            <main className="hero-gradient relative min-h-screen overflow-hidden px-6 py-16">
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(15,23,42,0.6),_transparent_70%)]" aria-hidden />
                <div className="relative mx-auto flex max-w-6xl flex-col gap-16">
                    <header className="flex flex-col items-center gap-6 text-center">
                        <span className="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1 text-sm uppercase tracking-[0.45em] text-brand-200">
                            Dungeon & Dragons Campaign Nexus
                        </span>
                        <h1 className="font-display text-4xl font-semibold text-white drop-shadow-md sm:text-5xl md:text-6xl">
                            A Turn-Based Multiverse for Collaborative Storytelling
                        </h1>
                        <p className="max-w-3xl text-lg text-slate-200/80 sm:text-xl">
                            Coordinate worlds, campaigns, and cross-party adventures. Automate time-based turns, empower dungeon masters with AI assistance, and keep every session logged with notes, modular maps, and shared progress trackers.
                        </p>
                        <div className="flex flex-wrap items-center justify-center gap-3">
                            <Button asChild size="lg" className="shadow-ambient">
                                <Link href={registerHref}>
                                    {heroCtaLabel}
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline">
                                <Link href={loginHref}>
                                    Sign In
                                </Link>
                            </Button>
                        </div>
                    </header>
                    <section className="grid gap-4 sm:grid-cols-2">
                        {features.map((feature) => (
                            <article
                                key={feature.title}
                                className="group relative overflow-hidden rounded-2xl border border-white/5 bg-slate-900/60 p-6 shadow-ambient transition hover:border-brand-400/60 hover:bg-slate-900/80"
                            >
                                <div className="absolute inset-0 bg-gradient-to-br from-brand-500/0 via-brand-500/10 to-brand-500/0 opacity-0 transition group-hover:opacity-100" aria-hidden />
                                <div className="relative flex flex-col gap-4">
                                    <feature.icon className="h-10 w-10 text-brand-300" />
                                    <h2 className="text-2xl font-semibold text-white">{feature.title}</h2>
                                    <p className="text-base text-slate-300/90">{feature.description}</p>
                                    <Button asChild variant="ghost" className="justify-start px-0 text-brand-200 hover:text-brand-100">
                                        <Link href={feature.href}>
                                            Explore
                                        </Link>
                                    </Button>
                                </div>
                            </article>
                        ))}
                    </section>
                </div>
            </main>
        </>
    );
}
