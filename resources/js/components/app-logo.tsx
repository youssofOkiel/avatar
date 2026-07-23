import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <div className="flex items-center gap-2 overflow-hidden">
            <AppLogoIcon className="h-10 w-auto max-w-[180px]" />
            <span className="sr-only">Avatar Educational Center</span>
        </div>
    );
}
