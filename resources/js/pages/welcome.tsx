import { Head, Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard, login } from '@/routes';

export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="أفاتار — المركز التعليمي" />
            <div className="relative flex min-h-screen flex-col overflow-hidden text-primary">
                <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_20%_0%,_#bfe3ef_0%,_transparent_50%),radial-gradient(ellipse_at_90%_10%,_#9ec9e0_0%,_transparent_45%),linear-gradient(165deg,#d7e4ef_0%,#e7eef5_42%,#c5d8e8_100%)]" />
                <div className="pointer-events-none absolute inset-0 opacity-[0.28] [background-image:linear-gradient(rgba(4,40,79,0.06)_1px,transparent_1px),linear-gradient(90deg,rgba(4,40,79,0.06)_1px,transparent_1px)] [background-size:48px_48px] [mask-image:radial-gradient(ellipse_at_center,black_20%,transparent_75%)]" />
                <div className="animate-float-slow pointer-events-none absolute -start-24 top-24 size-72 rounded-full bg-brand-cyan/25 blur-3xl" />
                <div className="animate-float-delayed pointer-events-none absolute -end-16 bottom-10 size-80 rounded-full bg-brand-blue/20 blur-3xl" />

                <header className="animate-fade-in relative z-10 mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-6 py-6">
                    <AppLogoIcon className="h-14 w-auto max-w-[180px]" />
                    <div className="flex items-center gap-2 sm:gap-3">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground transition hover:bg-brand-blue"
                            >
                                لوحة التحكم
                            </Link>
                        ) : (
                            <Link
                                href={login()}
                                className="inline-flex rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground transition hover:bg-brand-blue"
                            >
                                تسجيل الدخول
                            </Link>
                        )}
                    </div>
                </header>

                <main className="relative z-10 mx-auto flex w-full max-w-5xl flex-1 flex-col justify-center px-6 pb-20 pt-8">
                    <div className="animate-rise max-w-2xl">
                        <p className="mb-3 text-sm font-semibold tracking-[0.2em] text-brand-blue">
                            المركز التعليمي
                        </p>
                        <h1 className="text-4xl font-semibold tracking-tight text-primary sm:text-6xl">
                            أفاتار
                        </h1>
                        <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                            نظام بسيط لإدارة المراحل الدراسية والمواد والمعلمين
                            والحصص وحجوزات الطلاب.
                        </p>
                        <div className="mt-9 flex flex-wrap gap-3">
                            <Link
                                href={auth.user ? dashboard() : login()}
                                className="inline-flex rounded-md bg-[linear-gradient(135deg,#0a4f8f_0%,#2eb6d4_100%)] px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:opacity-95"
                            >
                                {auth.user ? 'الدخول إلى لوحة التحكم' : 'تسجيل الدخول'}
                            </Link>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}
