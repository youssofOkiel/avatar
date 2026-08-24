import { Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    CalendarRange,
    ClipboardList,
    LayoutGrid,
    Library,
    Presentation,
    Receipt,
    TrendingUp,
    UserRound,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const footerNavItems: NavItem[] = [];

const adminNav: NavItem[] = [
    { title: 'الجدول', href: '/admin/schedule', icon: CalendarRange },
    { title: 'حجوزات الطلاب', href: '/admin/reservations', icon: ClipboardList },
    { title: 'الطلاب', href: '/admin/students', icon: UserRound },
    { title: 'حجز قاعة', href: '/admin/sessions', icon: CalendarDays },
    { title: 'المعلمون', href: '/admin/teachers', icon: Users },
    { title: 'محاضرون خارجيون', href: '/admin/external-lecturers', icon: Presentation },
    { title: 'المواد', href: '/admin/subjects', icon: Library },
    { title: 'المصروفات', href: '/admin/expenses', icon: Receipt },
];

const superadminNav: NavItem[] = [
    { title: 'لوحة التحكم', href: '/dashboard', icon: LayoutGrid },
    { title: 'التقارير', href: '/admin/reports', icon: TrendingUp },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isSuperadmin = auth.user?.role === 'superadmin';

    const items = isSuperadmin ? [...superadminNav, ...adminNav] : adminNav;

    return (
        <Sidebar collapsible="icon" variant="inset" side="right">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
