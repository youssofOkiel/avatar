import { Link } from '@inertiajs/react';
import {
    CalendarDays,
    ClipboardList,
    GraduationCap,
    Library,
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
    { title: 'الحجوزات', href: '/admin/reservations', icon: ClipboardList },
    { title: 'الطلاب', href: '/admin/students', icon: UserRound },
    { title: 'الحصص', href: '/admin/sessions', icon: CalendarDays },
    { title: 'المعلمون', href: '/admin/teachers', icon: Users },
    { title: 'المواد', href: '/admin/subjects', icon: Library },
    { title: 'المراحل الدراسية', href: '/admin/education-levels', icon: GraduationCap },
];

export function AppSidebar() {
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
                <NavMain items={adminNav} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
